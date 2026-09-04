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
        $tenantName = (string) ($holder->tenant?->name ?? 'DANUM');
        $commonName = trim($user->name) !== '' ? $user->name : (string) $user->email;
        $validFrom = now()->startOfSecond();
        $validUntil = $validFrom->copy()->addYear();

        $certificateConfig = base_path('resources/certificates/openssl.cnf');
        if (! is_file($certificateConfig)) throw new RuntimeException('Konfigurasi OpenSSL sertifikat tidak ditemukan.');

        $previousOpenSslConf = getenv('OPENSSL_CONF');
        putenv('OPENSSL_CONF='.$certificateConfig);

        try {
            $opensslOptions = [
                'config' => $certificateConfig,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 3072,
            ];

            $key = openssl_pkey_new($opensslOptions);
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

            $csr = openssl_csr_new($dn, $key, [
                'config' => $certificateConfig,
                'digest_alg' => 'sha256',
            ]);
            if ($csr === false) throw new RuntimeException('Gagal membuat CSR sertifikat: '.$this->opensslError());

            $cert = openssl_csr_sign($csr, null, $key, 365, [
                'config' => $certificateConfig,
                'digest_alg' => 'sha256',
                'x509_extensions' => 'v3_req',
            ], 0);
            if ($cert === false) throw new RuntimeException('Gagal menerbitkan sertifikat publik: '.$this->opensslError());

            if (! openssl_x509_export($cert, $certificatePem)) throw new RuntimeException('Gagal mengekspor sertifikat publik: '.$this->opensslError());
            if (! openssl_pkey_export($key, $privateKeyPem, null, ['config' => $certificateConfig])) throw new RuntimeException('Gagal mengekspor private key: '.$this->opensslError());

            $parsed = openssl_x509_parse($certificatePem);
            $serial = strtoupper((string) ($parsed['serialNumberHex'] ?? ''));
            if ($serial === '') $serial = strtoupper((string) ($parsed['serialNumber'] ?? ''));
            if ($serial === '') throw new RuntimeException('Gagal membaca serial number sertifikat publik.');

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
                    'serial_number' => $serial,
                    'certificate_pem' => $certificatePem,
                    'private_key_encrypted' => Crypt::encryptString($privateKeyPem),
                    'fingerprint_sha256' => $fingerprint,
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'is_active' => true,
                    'generated_by' => $generatedBy->id,
                    'metadata' => [
                        'subject' => $parsed['subject'] ?? [],
                        'issuer' => $parsed['issuer'] ?? [],
                        'signature_algorithm' => $parsed['signatureTypeSN'] ?? null,
                    ],
                ]);

                app(AuditLogService::class)->record('signer_certificate.generated', $generatedBy, $certificate, null, [
                    'position_id' => $position->id,
                    'user_id' => $holder->user_id,
                    'serial_number' => $serial,
                    'fingerprint_sha256' => $fingerprint,
                    'valid_from' => $validFrom->toIso8601String(),
                    'valid_until' => $validUntil->toIso8601String(),
                ]);

                return $certificate;
            });
        } finally {
            if ($previousOpenSslConf === false) putenv('OPENSSL_CONF');
            else putenv('OPENSSL_CONF='.$previousOpenSslConf);
        }
    }

    private function opensslError(): string
    {
        $errors = [];
        while (($error = openssl_error_string()) !== false) $errors[] = $error;
        return implode(' | ', $errors) ?: 'OpenSSL tidak memberikan detail error.';
    }
}
