<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Models\SignerCertificate;
use App\Services\PdfSigningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfSigningServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_pdf_can_be_signed_with_pades_b_b(): void
    {
        Storage::fake('local');

        [$certificatePem, $privateKeyPem] = $this->certificateMaterial();
        $certificate = new SignerCertificate([
            'certificate_pem' => $certificatePem,
            'private_key_encrypted' => Crypt::encryptString($privateKeyPem),
            'valid_from' => now()->subMinute(),
            'valid_until' => now()->addYear(),
            'is_active' => true,
            'revoked_at' => null,
        ]);
        $certificate->exists = true;

        $sourcePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'danum-source-' . bin2hex(random_bytes(6)) . '.pdf';
        file_put_contents($sourcePath, $this->minimalPdf());

        try {
            $signedPath = app(PdfSigningService::class)->sign(
                sourcePdfPath: $sourcePath,
                certificate: $certificate,
                signerName: 'Penguji DANUM',
                reason: 'Pengujian PAdES B-B',
            );

            $this->assertTrue(Storage::disk('local')->exists($signedPath));
            $signedPdf = Storage::disk('local')->get($signedPath);
            $this->assertStringStartsWith('%PDF-', $signedPdf);
            $this->assertStringContainsString('/ETSI.CAdES.detached', $signedPdf);
            $this->assertNotSame(file_get_contents($sourcePath), $signedPdf);
        } finally {
            @unlink($sourcePath);
        }
    }

    public function test_unusable_certificate_is_rejected_before_signing(): void
    {
        Storage::fake('local');

        $certificate = new SignerCertificate([
            'certificate_pem' => 'invalid',
            'private_key_encrypted' => Crypt::encryptString('invalid'),
            'valid_from' => now()->subYear(),
            'valid_until' => now()->subDay(),
            'is_active' => true,
            'revoked_at' => null,
        ]);
        $certificate->exists = true;

        $sourcePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'danum-source-' . bin2hex(random_bytes(6)) . '.pdf';
        file_put_contents($sourcePath, $this->minimalPdf());

        try {
            $this->expectException(\DomainException::class);
            app(PdfSigningService::class)->sign($sourcePath, $certificate, 'Penguji DANUM', 'Test');
        } finally {
            @unlink($sourcePath);
        }
    }

    private function certificateMaterial(): array
    {
        $config = base_path('resources/certificates/openssl.cnf');
        $previous = getenv('OPENSSL_CONF');
        putenv('OPENSSL_CONF=' . $config);

        try {
            $key = openssl_pkey_new([
                'config' => $config,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ]);
            $this->assertNotFalse($key);

            $csr = openssl_csr_new(['commonName' => 'DANUM Test Signer'], $key, [
                'config' => $config,
                'digest_alg' => 'sha256',
            ]);
            $this->assertNotFalse($csr);

            $cert = openssl_csr_sign($csr, null, $key, 365, [
                'config' => $config,
                'digest_alg' => 'sha256',
                'x509_extensions' => 'v3_req',
            ]);
            $this->assertNotFalse($cert);

            $certificatePem = '';
            $privateKeyPem = '';
            $this->assertTrue(openssl_x509_export($cert, $certificatePem));
            $this->assertTrue(openssl_pkey_export($key, $privateKeyPem, null, ['config' => $config]));

            return [$certificatePem, $privateKeyPem];
        } finally {
            if ($previous === false) putenv('OPENSSL_CONF');
            else putenv('OPENSSL_CONF=' . $previous);
        }
    }

    private function minimalPdf(): string
    {
        if (! defined('K_PATH_FONTS')) {
            $fontPath = realpath(base_path('vendor/tecnickcom/tc-lib-pdf-font/target/fonts'));
            if ($fontPath !== false) define('K_PATH_FONTS', $fontPath);
        }

        $pdf = new \Com\Tecnick\Pdf\Tcpdf();
        $pdf->setCreator('DANUM Test');
        $pdf->setTitle('DANUM PDF signing test');
        $pdf->addPage(['format' => 'A4']);
        return $pdf->getOutPDFString();
    }
}
