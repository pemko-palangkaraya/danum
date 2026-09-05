<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CertificateAuthority;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CertificateAuthorityService
{
    public function ensureHierarchy(): array
    {
        $root = CertificateAuthority::query()->where('type', 'root')->where('is_active', true)->latest('created_at')->first();
        $issuing = CertificateAuthority::query()->where('type', 'issuing')->where('is_active', true)->latest('created_at')->first();

        if ($root && $issuing && $issuing->parent_id === $root->id && $root->isUsable() && $issuing->isUsable()) {
            return [$root, $issuing];
        }

        return DB::transaction(function (): array {
            $root = CertificateAuthority::query()->where('type', 'root')->where('is_active', true)->latest('created_at')->first();
            if (! $root || ! $root->isUsable()) {
                $root = $this->generateRoot();
            }

            $issuing = CertificateAuthority::query()->where('type', 'issuing')->where('is_active', true)->latest('created_at')->first();
            if (! $issuing || ! $issuing->isUsable() || $issuing->parent_id !== $root->id) {
                $issuing = $this->generateIssuing($root);
            }

            return [$root, $issuing];
        });
    }

    public function chainForSigner(CertificateAuthority $issuing): string
    {
        if ($issuing->type !== 'issuing' || ! $issuing->isUsable()) {
            throw new RuntimeException('Issuing CA DANUM tidak aktif atau tidak valid.');
        }

        $root = $issuing->parent;
        if (! $root || $root->type !== 'root' || ! $root->isUsable()) {
            throw new RuntimeException('Root CA DANUM tidak tersedia atau tidak valid.');
        }

        return trim($issuing->certificate_pem) . "\n" . trim($root->certificate_pem) . "\n";
    }

    public function issueSignerCertificate(array $dn, int $days = 365): array
    {
        [$root, $issuing] = $this->ensureHierarchy();
        $config = base_path('resources/certificates/openssl.cnf');
        if (! is_file($config)) throw new RuntimeException('Konfigurasi OpenSSL sertifikat tidak ditemukan.');

        $previous = getenv('OPENSSL_CONF');
        putenv('OPENSSL_CONF='.$config);

        try {
            $key = openssl_pkey_new([
                'config' => $config,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 3072,
            ]);
            if ($key === false) throw new RuntimeException('Gagal membuat private key signer: '.$this->opensslError());

            $csr = openssl_csr_new($dn, $key, ['config' => $config, 'digest_alg' => 'sha256']);
            if ($csr === false) throw new RuntimeException('Gagal membuat CSR signer: '.$this->opensslError());

            $issuerKey = openssl_pkey_get_private(Crypt::decryptString($issuing->private_key_encrypted));
            if ($issuerKey === false) throw new RuntimeException('Private key Issuing CA tidak dapat dibuka.');

            $serialNumber = random_int(1, PHP_INT_MAX);
            $cert = openssl_csr_sign($csr, $issuing->certificate_pem, $issuerKey, $days, [
                'config' => $config,
                'digest_alg' => 'sha256',
                'x509_extensions' => 'v3_signer',
            ], $serialNumber);
            openssl_free_key($issuerKey);

            if ($cert === false) throw new RuntimeException('Gagal menerbitkan sertifikat signer: '.$this->opensslError());
            if (! openssl_x509_export($cert, $certificatePem)) throw new RuntimeException('Gagal mengekspor sertifikat signer: '.$this->opensslError());
            if (! openssl_pkey_export($key, $privateKeyPem, null, ['config' => $config])) throw new RuntimeException('Gagal mengekspor private key signer: '.$this->opensslError());

            $parsed = openssl_x509_parse($certificatePem);
            if ($parsed === false) throw new RuntimeException('Gagal membaca sertifikat signer.');
            $fingerprint = openssl_x509_fingerprint($certificatePem, 'sha256');
            $serial = strtoupper((string) ($parsed['serialNumberHex'] ?? $parsed['serialNumber'] ?? ''));
            if (! $fingerprint || $serial === '' || preg_match('/^0+$/', $serial) === 1) throw new RuntimeException('Identitas sertifikat signer tidak valid.');

            $tz = (string) config('app.timezone', 'UTC');
            return [
                'issuing_ca' => $issuing,
                'certificate_pem' => $certificatePem,
                'private_key_pem' => $privateKeyPem,
                'serial' => $serial,
                'fingerprint' => $fingerprint,
                'valid_from' => CarbonImmutable::createFromTimestamp((int) $parsed['validFrom_time_t'], $tz),
                'valid_until' => CarbonImmutable::createFromTimestamp((int) $parsed['validTo_time_t'], $tz),
                'metadata' => [
                    'subject' => $parsed['subject'] ?? [],
                    'issuer' => $parsed['issuer'] ?? [],
                    'signature_algorithm' => $parsed['signatureTypeSN'] ?? null,
                    'issuer_ca_id' => $issuing->id,
                    'root_ca_id' => $root->id,
                ],
            ];
        } finally {
            if ($previous === false) putenv('OPENSSL_CONF');
            else putenv('OPENSSL_CONF='.$previous);
        }
    }

    private function generateRoot(): CertificateAuthority
    {
        return $this->generateCa('root', 'DANUM Root CA', null, 3650);
    }

    private function generateIssuing(CertificateAuthority $root): CertificateAuthority
    {
        return $this->generateCa('issuing', 'DANUM Issuing CA', $root, 1825);
    }

    private function generateCa(string $type, string $name, ?CertificateAuthority $parent, int $days): CertificateAuthority
    {
        $config = base_path('resources/certificates/openssl.cnf');
        $previous = getenv('OPENSSL_CONF');
        putenv('OPENSSL_CONF='.$config);

        try {
            $key = openssl_pkey_new(['config' => $config, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 4096]);
            if ($key === false) throw new RuntimeException('Gagal membuat private key CA: '.$this->opensslError());

            $dn = ['countryName' => 'ID', 'organizationName' => 'DANUM', 'organizationalUnitName' => 'Certificate Authority', 'commonName' => $name];
            $csr = openssl_csr_new($dn, $key, ['config' => $config, 'digest_alg' => 'sha256']);
            if ($csr === false) throw new RuntimeException('Gagal membuat CSR CA: '.$this->opensslError());

            $issuerCert = null;
            $issuerKey = null;
            if ($parent) {
                $issuerCert = $parent->certificate_pem;
                $issuerKey = openssl_pkey_get_private(Crypt::decryptString($parent->private_key_encrypted));
                if ($issuerKey === false) throw new RuntimeException('Private key Root CA tidak dapat dibuka.');
            } else {
                $issuerKey = $key;
            }

            $serialNumber = random_int(1, PHP_INT_MAX);
            $cert = openssl_csr_sign($csr, $issuerCert, $issuerKey, $days, [
                'config' => $config,
                'digest_alg' => 'sha256',
                'x509_extensions' => $type === 'root' ? 'v3_root_ca' : 'v3_issuing_ca',
            ], $serialNumber);
            if ($parent && $issuerKey !== false) openssl_free_key($issuerKey);
            if ($cert === false) throw new RuntimeException('Gagal menerbitkan CA: '.$this->opensslError());

            if (! openssl_x509_export($cert, $certificatePem)) throw new RuntimeException('Gagal mengekspor CA: '.$this->opensslError());
            if (! openssl_pkey_export($key, $privateKeyPem, null, ['config' => $config])) throw new RuntimeException('Gagal mengekspor private key CA: '.$this->opensslError());
            $parsed = openssl_x509_parse($certificatePem);
            $fingerprint = openssl_x509_fingerprint($certificatePem, 'sha256');
            if ($parsed === false || ! $fingerprint) throw new RuntimeException('Gagal membaca identitas CA.');

            $tz = (string) config('app.timezone', 'UTC');
            return CertificateAuthority::query()->create([
                'type' => $type,
                'name' => $name,
                'parent_id' => $parent?->id,
                'serial_number' => strtoupper((string) ($parsed['serialNumberHex'] ?? $parsed['serialNumber'] ?? '')),
                'fingerprint_sha256' => $fingerprint,
                'certificate_pem' => $certificatePem,
                'private_key_encrypted' => Crypt::encryptString($privateKeyPem),
                'valid_from' => CarbonImmutable::createFromTimestamp((int) $parsed['validFrom_time_t'], $tz),
                'valid_until' => CarbonImmutable::createFromTimestamp((int) $parsed['validTo_time_t'], $tz),
                'is_active' => true,
                'metadata' => ['subject' => $parsed['subject'] ?? [], 'issuer' => $parsed['issuer'] ?? []],
            ]);
        } finally {
            if ($previous === false) putenv('OPENSSL_CONF');
            else putenv('OPENSSL_CONF='.$previous);
        }
    }

    private function opensslError(): string
    {
        $errors = [];
        while (($error = openssl_error_string()) !== false) $errors[] = $error;
        return implode(' | ', $errors) ?: 'OpenSSL tidak memberikan detail error.';
    }
}
