<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SignerCertificate;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PdfSigningService
{
    /**
     * Sign an existing PDF with a PAdES B-T signature and RFC 3161 TSA timestamp.
     */
    public function sign(
        string $sourcePdfPath,
        SignerCertificate $certificate,
        string $signerName,
        string $reason,
        ?string $outputPath = null,
    ): string {
        $sourceAbsolutePath = $this->resolveStoragePath($sourcePdfPath);
        if (! is_file($sourceAbsolutePath) || ! is_readable($sourceAbsolutePath)) {
            throw new RuntimeException('PDF sumber untuk tanda tangan tidak ditemukan.');
        }

        if (! $certificate->isUsable()) {
            throw new \DomainException('Sertifikat TTE penanda tangan tidak aktif, sudah dicabut, atau sudah kedaluwarsa.');
        }

        $certificatePem = trim((string) $certificate->certificate_pem);
        $privateKeyPem = Crypt::decryptString((string) $certificate->private_key_encrypted);

        if ($certificatePem === '' || $privateKeyPem === '') {
            throw new RuntimeException('Material sertifikat TTE tidak lengkap.');
        }

        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if ($privateKey === false || ! openssl_x509_check_private_key($certificatePem, $privateKey)) {
            if ($privateKey !== false) {
                openssl_free_key($privateKey);
            }
            throw new RuntimeException('Private key tidak cocok dengan sertifikat publik penanda tangan.');
        }
        openssl_free_key($privateKey);

        $outputPath ??= 'outgoing-letters/signed/' . date('Y/m') . '/' . pathinfo($sourcePdfPath, PATHINFO_FILENAME) . '-signed.pdf';

        $this->configureFonts();

        // Resolve the tenant from the certificate owner so the PDF signature
        // metadata identifies the institution that owns the signing certificate.
        $certificate->loadMissing('user.tenant');
        $tenantName = trim((string) $certificate->user?->tenant?->name);

        $pdf = new \Com\Tecnick\Pdf\Tcpdf();
        $pdf->setCreator('DANUM');
        $pdf->setAuthor($signerName);
        $pdf->setSubject('Surat Keluar - Tanda Tangan Elektronik');
        $pdf->setTitle('Surat Keluar - Ditandatangani Secara Elektronik');

        $sourceId = $pdf->setImportSourceFile($sourceAbsolutePath);
        $pageCount = $pdf->getSourcePageCount($sourceId);

        if ($pageCount < 1) {
            throw new RuntimeException('PDF sumber tidak memiliki halaman.');
        }

        $pdf->appendDocument($sourceId);

        // PAdES B-T = PAdES B-B plus an RFC 3161 signature timestamp.
        // The TSA endpoint is configurable so production can use an institutional
        // or other trusted TSA instead of the public demo endpoint.
        $pdf->signature()
            ->configure([
                'profile' => \Com\Tecnick\Pdf\Sign\Config::PROFILE_PADES_B_T,
                'digest_algorithm' => \Com\Tecnick\Pdf\Sign\DigestAlgorithm::Sha256->value,
                'cert_type' => 2,
                'signcert' => $certificatePem,
                'privkey' => $privateKeyPem,
                'password' => '',
                'info' => [
                    'Name' => $signerName,
                    'Reason' => trim($reason) !== '' ? $reason : 'Dokumen disetujui dan ditandatangani secara elektronik.',
                    'Location' => $tenantName !== '' ? $tenantName : 'DANUM',
                ],
            ])
            ->timestamp([
                'enabled' => true,
                'host' => (string) config('services.tsa.url', 'https://freetsa.org/tsr'),
                'username' => (string) config('services.tsa.username', ''),
                'password' => (string) config('services.tsa.password', ''),
                'cert' => (string) config('services.tsa.certificate', ''),
                'hash_algorithm' => 'sha256',
                'policy_oid' => (string) config('services.tsa.policy_oid', ''),
                'nonce_enabled' => true,
                'timeout' => (int) config('services.tsa.timeout', 30),
                'verify_peer' => (bool) config('services.tsa.verify_peer', true),
            ]);

        $pdf->signature()->appearance()->place(
            posx: 15,
            posy: 15,
            width: 70,
            height: 18,
            page: -1,
            name: 'TandaTanganElektronik',
        );

        $rawPdf = $pdf->getOutPDFString();

        if ($rawPdf === '') {
            throw new RuntimeException('PDF bertanda tangan tidak menghasilkan data.');
        }

        Storage::disk('local')->put($outputPath, $rawPdf);

        return $outputPath;
    }

    private function resolveStoragePath(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            throw new RuntimeException('File PDF tidak ditemukan pada storage.');
        }

        return $disk->path($path);
    }

    private function configureFonts(): void
    {
        if (! defined('K_PATH_FONTS')) {
            $fontPath = realpath(base_path('vendor/tecnickcom/tc-lib-pdf-font/target/fonts'));

            if ($fontPath !== false) {
                define('K_PATH_FONTS', $fontPath);
            }
        }
    }
}
