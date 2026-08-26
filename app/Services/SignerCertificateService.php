<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\SignerCertificate;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SignerCertificateService
{
    public function generate(Position $position, PositionHolder $holder, User $generatedBy): SignerCertificate
    {
        if (! $position->can_sign) throw new RuntimeException('Jabatan ini belum diizinkan sebagai penandatangan.');
        if ($holder->position_id !== $position->id || $holder->ended_at !== null || $holder->started_at?->isFuture()) throw new RuntimeException('Pemegang jabatan aktif tidak valid.');
        if (! $holder->user) throw new RuntimeException('User penanda tangan tidak ditemukan.');

        $user = $holder->user;
        $tenantName = (string) ($position->tenant?->name ?? 'DANUM');
        $commonName = trim($user->name) !== '' ? $user->name : (string) $user->email;
        $serial = strtoupper(bin2hex(random_bytes(16)));
        $validFrom = now()->startOfSecond();
        $validUntil = $validFrom->copy()->addYear();

        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 3072,
        ]);
        if ($key === false) throw new RuntimeException('Gagal membuat private key sertifikat: '.$this->opensslError());

        $dn = [
            'countryName' => 'ID',
            'stateOrProvinceName' => 'Kalimantan Tengah',
            'localityName' => $tenantName,
            'organizationName' => $tenantName,
            'organizationalUnitName' => $position->name,
            'commonName' => $commonName,
            'emailAddress' => (string) $user->email,
        ];

        $csr = openssl_csr_new($dn, $key, ['digest_alg' => 'sha256']);
        if ($csr === false) throw new RuntimeException('Gagal membuat CSR sertifikat: '.$this->opensslError());

        $certificateConfig = base_path('resources/certificates/openssl.cnf');
        if (! is_file($certificateConfig)) throw new RuntimeException('Konfigurasi OpenSSL sertifikat tidak ditemukan.');

        $previousOpenSslConf = getenv('OPENSSL_CONF');
        putenv('OPENSSL_CONF='.$certificateConfig);

        try {
            $cert = openssl_csr_sign($csr, null, $key, 365, [
                'digest_alg' => 'sha256',
                'x509_extensions' => 'v3_req',
            ], 0);
        } finally {
            if ($previousOpenSslConf === false) {
                putenv('OPENSSL_CONF');
            } else {
                putenv('OPENSSL_CONF='.$previousOpenSslConf);
            }
        }

        if ($cert === false) throw new RuntimeException('Gagal menerbitkan sertifikat publik: '.$this->opensslError());

        if (! openssl_x509_export($cert, $certificatePem)) throw new RuntimeException('Gagal mengekspor sertifikat publik: '.$this->opensslError());
        if (! openssl_pkey_export($key, $privateKeyPem)) throw new RuntimeException('Gagal mengekspor private key: '.$this->opensslError());

        $parsed = openssl_x509_parse($certificatePem);
        $fingerprint = openssl_x509_fingerprint($certificatePem, 'sha256');
        if (! $fingerprint) throw new RuntimeException('Gagal menghitung fingerprint sertifikat.');

        return DB::transaction(function () use ($position, $holder, $generatedBy, $serial, $validFrom, $validUntil, $certificatePem, $privateKeyPem, $parsed, $fingerprint): SignerCertificate {
            SignerCertificate::query()
                ->where('position_id', $position->id)
                ->where('user_id', $holder->user_id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'revoked_at' => now()]);

            $certificate = SignerCertificate::query()->create([
                'position_id' => $position->id,
                'user_id' => $holder->user_id,
                'type' => 'self_signed',
                'serial_number' => (string) ($parsed['serialNumberHex'] ?? $serial),
                'fingerprint_sha256' => strtoupper($fingerprint),
                'certificate_pem' => $certificatePem,
                'private_key_encrypted' => Crypt::encryptString($privateKeyPem),
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'is_active' => true,
                'generated_by' => $generatedBy->id,
            ]);

            app(AuditLogService::class)->record(
                'signer_certificate.generated',
                $generatedBy,
                $certificate,
                null,
                [
                    'position_id' => $position->id,
                    'user_id' => $holder->user_id,
                    'type' => $certificate->type,
                    'serial_number' => $certificate->serial_number,
                    'fingerprint_sha256' => $certificate->fingerprint_sha256,
                    'valid_from' => $certificate->valid_from->toIso8601String(),
                    'valid_until' => $certificate->valid_until->toIso8601String(),
                ]
            );

            return $certificate;
        });
    }

    public function privateKey(SignerCertificate $certificate): string
    {
        return Crypt::decryptString($certificate->private_key_encrypted);
    }

    private function opensslError(): string
    {
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        return $errors !== [] ? implode(' | ', $errors) : 'unknown OpenSSL error';
    }
}
