<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CertificateAuthority;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class CertificateAuthorityGenerator
{
    public function generateRoot(): CertificateAuthority
    {
        return $this->generate('root', 'DANUM Root CA', null, 3650);
    }

    public function generateIssuing(CertificateAuthority $root): CertificateAuthority
    {
        return $this->generate('issuing', 'DANUM Issuing CA', $root, 1825);
    }

    private function generate(string $type, string $name, ?CertificateAuthority $parent, int $days): CertificateAuthority
    {
        $config = base_path('resources/certificates/openssl.cnf');
        $previous = getenv('OPENSSL_CONF');
        putenv('OPENSSL_CONF='.$config);

        try {
            $key = openssl_pkey_new([
                'config' => $config,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 4096,
            ]);
            if ($key === false) {
                throw new RuntimeException('Gagal membuat private key CA: '.$this->opensslError());
            }

            $dn = [
                'countryName' => 'ID',
                'organizationName' => 'DANUM',
                'organizationalUnitName' => 'Certificate Authority',
                'commonName' => $name,
            ];
            $csr = openssl_csr_new($dn, $key, ['config' => $config, 'digest_alg' => 'sha256']);
            if ($csr === false) {
                throw new RuntimeException('Gagal membuat CSR CA: '.$this->opensslError());
            }

            $issuerKey = $key;
            if ($parent) {
                $issuerKey = openssl_pkey_get_private(Crypt::decryptString($parent->private_key_encrypted));
                if ($issuerKey === false) {
                    throw new RuntimeException('Private key Root CA tidak dapat dibuka.');
                }
            }

            $cert = openssl_csr_sign(
                $csr,
                $parent?->certificate_pem,
                $issuerKey,
                $days,
                [
                    'config' => $config,
                    'digest_alg' => 'sha256',
                    'x509_extensions' => $type === 'root' ? 'v3_root_ca' : 'v3_issuing_ca',
                ],
                random_int(1, PHP_INT_MAX),
            );

            if ($parent) {
                openssl_free_key($issuerKey);
            }
            if ($cert === false) {
                throw new RuntimeException('Gagal menerbitkan CA: '.$this->opensslError());
            }

            if (! openssl_x509_export($cert, $certificatePem)) {
                throw new RuntimeException('Gagal mengekspor CA: '.$this->opensslError());
            }
            if (! openssl_pkey_export($key, $privateKeyPem, null, ['config' => $config])) {
                throw new RuntimeException('Gagal mengekspor private key CA: '.$this->opensslError());
            }

            $parsed = openssl_x509_parse($certificatePem);
            $fingerprint = openssl_x509_fingerprint($certificatePem, 'sha256');
            if ($parsed === false || ! $fingerprint) {
                throw new RuntimeException('Gagal membaca identitas CA.');
            }

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
                'metadata' => [
                    'subject' => $parsed['subject'] ?? [],
                    'issuer' => $parsed['issuer'] ?? [],
                ],
            ]);
        } finally {
            if ($previous === false) {
                putenv('OPENSSL_CONF');
            } else {
                putenv('OPENSSL_CONF='.$previous);
            }
        }
    }

    private function opensslError(): string
    {
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        return implode(' | ', $errors) ?: 'OpenSSL tidak memberikan detail error.';
    }
}
