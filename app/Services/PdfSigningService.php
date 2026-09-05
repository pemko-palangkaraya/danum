<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SignerCertificate;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PdfSigningService
{
    private const DEFAULT_REASON = 'Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh Data Administrasi dan Urusan Masyarakat (DANUM).';

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
        $stage = 'resolve-source-pdf';
        $sourceAbsolutePath = $this->resolveStoragePath($sourcePdfPath);
        if (! is_file($sourceAbsolutePath) || ! is_readable($sourceAbsolutePath)) throw new RuntimeException('PDF sumber untuk tanda tangan tidak ditemukan.');
        if (! $certificate->isUsable()) throw new \DomainException('Sertifikat TTE penanda tangan tidak aktif, sudah dicabut, atau sudah kedaluwarsa.');

        $certificatePem = trim((string) $certificate->certificate_pem);
        $privateKeyPem = '';

        try {
            $stage = 'decrypt-private-key';
            $privateKeyPem = Crypt::decryptString((string) $certificate->private_key_encrypted);
            if ($certificatePem === '' || $privateKeyPem === '') throw new RuntimeException('Material sertifikat TTE tidak lengkap.');

            $stage = 'validate-private-key';
            $privateKey = openssl_pkey_get_private($privateKeyPem);
            if ($privateKey === false || ! openssl_x509_check_private_key($certificatePem, $privateKey)) {
                if ($privateKey !== false) openssl_free_key($privateKey);
                throw new RuntimeException('Private key tidak cocok dengan sertifikat publik penanda tangan.');
            }
            openssl_free_key($privateKey);

            $outputPath ??= 'outgoing-letters/signed/' . now()->format('Y/m') . '/' . pathinfo($sourcePdfPath, PATHINFO_FILENAME) . '-signed.pdf';
            $this->configureFonts();

            $certificate->loadMissing('user.tenant');
            $tenantName = trim((string) $certificate->user?->tenant?->name);

            $previousTimezone = date_default_timezone_get();
            $applicationTimezone = (string) config('app.timezone', 'UTC');
            date_default_timezone_set($applicationTimezone);
            $signingTime = now()->startOfSecond();

            try {
                $stage = 'create-pdf-document';
                $pdf = new class extends \Com\Tecnick\Pdf\Tcpdf {
                    public function setDocumentTimestamps(int $timestamp): void
                    {
                        $this->doctime = $timestamp;
                        $this->docmodtime = $timestamp;
                    }
                };
                $pdf->setDocumentTimestamps($signingTime->timestamp);
                $pdf->setCreator('DANUM');
                $pdf->setAuthor($signerName);
                $pdf->setSubject('Surat Keluar - Tanda Tangan Elektronik');
                $pdf->setTitle('Surat Keluar - Ditandatangani Secara Elektronik');

                $stage = 'import-source-pdf';
                $sourceId = $pdf->setImportSourceFile($sourceAbsolutePath);
                $pageCount = $pdf->getSourcePageCount($sourceId);
                if ($pageCount < 1) throw new RuntimeException('PDF sumber tidak memiliki halaman.');
                $pdf->appendDocument($sourceId);

                $stage = 'configure-pades-timestamp';
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
                            'Reason' => self::DEFAULT_REASON,
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

                $stage = 'place-signature-appearance';
                $pdf->signature()->appearance()->place(posx: 15, posy: 15, width: 70, height: 18, page: -1, name: 'TandaTanganElektronik');

                $stage = 'generate-signed-pdf';
                $rawPdf = $pdf->getOutPDFString();
                if ($rawPdf === '') throw new RuntimeException('PDF bertanda tangan tidak menghasilkan data.');

                $stage = 'store-signed-pdf';
                Storage::disk('local')->put($outputPath, $rawPdf);
                return $outputPath;
            } finally {
                date_default_timezone_set($previousTimezone);
            }
        } catch (\Throwable $exception) {
            Log::error('Outgoing letter TTE signing failed.', [
                'stage' => $stage,
                'source_pdf_path' => $sourcePdfPath,
                'output_path' => $outputPath,
                'certificate_id' => $certificate->id,
                'signer_user_id' => $certificate->user_id,
                'signer_position_id' => $certificate->position_id,
                'tsa_host' => (string) config('services.tsa.url', 'https://freetsa.org/tsr'),
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
            ]);

            if ($exception instanceof \DomainException) throw $exception;
            throw new \DomainException('TTE gagal pada tahap ' . $stage . ': ' . $exception->getMessage(), previous: $exception);
        } finally {
            unset($privateKeyPem);
        }
    }

    private function resolveStoragePath(string $path): string
    {
        if (is_file($path)) return $path;
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) throw new RuntimeException('File PDF tidak ditemukan pada storage.');
        return $disk->path($path);
    }

    private function configureFonts(): void
    {
        if (! defined('K_PATH_FONTS')) {
            $fontPath = realpath(base_path('vendor/tecnickcom/tc-lib-pdf-font/target/fonts'));
            if ($fontPath !== false) define('K_PATH_FONTS', $fontPath);
        }
    }
}
