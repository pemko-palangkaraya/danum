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
     * Sign an existing PDF with the supplied signer certificate using PAdES B-B.
     * The source PDF is imported page-by-page so its rendered appearance is preserved.
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

        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'danum-tte-' . bin2hex(random_bytes(8));
        if (! mkdir($tempDir, 0700, true) && ! is_dir($tempDir)) {
            throw new RuntimeException('Direktori sementara TTE tidak dapat dibuat.');
        }

        $certPath = $tempDir . DIRECTORY_SEPARATOR . 'signer.crt';
        $keyPath = $tempDir . DIRECTORY_SEPARATOR . 'signer.key';
        $outputPath ??= 'outgoing-letters/signed/' . date('Y/m') . '/' . pathinfo($sourcePdfPath, PATHINFO_FILENAME) . '-signed.pdf';

        try {
            if (
                file_put_contents($certPath, $certificatePem, LOCK_EX) === false
                || file_put_contents($keyPath, $privateKeyPem, LOCK_EX) === false
            ) {
                throw new RuntimeException('Material sertifikat TTE sementara tidak dapat disiapkan.');
            }

            @chmod($certPath, 0600);
            @chmod($keyPath, 0600);

            $this->configureFonts();

            $pdf = new \Com\Tecnick\Pdf\Tcpdf();
            $pdf->setCreator('DANUM');
            $pdf->setAuthor($signerName);
            $pdf->setSubject('Dokumen bertanda tangan elektronik DANUM');
            $pdf->setTitle('Dokumen TTE DANUM');

            $sourceId = $pdf->setImportSourceFile($sourceAbsolutePath);
            $pageCount = $pdf->getSourcePageCount($sourceId);

            if ($pageCount < 1) {
                throw new RuntimeException('PDF sumber tidak memiliki halaman.');
            }

            $pdf->appendDocument($sourceId);

            /*
             * tc-lib-pdf 8.71.x accepts signcert/privkey as PEM strings or file://
             * sources. For a self-signed development certificate there is no issuer
             * chain, so omit the extracerts key entirely rather than passing an empty
             * value. The library treats the option as optional.
             */
            $pdf->signature()->configure([
                'profile' => \Com\Tecnick\Pdf\Sign\Config::PROFILE_PADES_B_B,
                'digest_algorithm' => \Com\Tecnick\Pdf\Sign\DigestAlgorithm::Sha256->value,
                'cert_type' => 2,
                'signcert' => 'file://' . $certPath,
                'privkey' => 'file://' . $keyPath,
                'password' => '',
                'info' => [
                    'Name' => $signerName,
                    'Reason' => $reason,
                    'Location' => 'DANUM',
                ],
            ]);

            $pdf->signature()->appearance()->place(
                posx: 15,
                posy: 15,
                width: 70,
                height: 18,
                page: -1,
                name: 'DANUMSignature',
            );

            $rawPdf = $pdf->getOutPDFString();

            if ($rawPdf === '') {
                throw new RuntimeException('PDF bertanda tangan tidak menghasilkan data.');
            }

            Storage::disk('local')->put($outputPath, $rawPdf);

            return $outputPath;
        } finally {
            @unlink($certPath);
            @unlink($keyPath);
            @rmdir($tempDir);
        }
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
