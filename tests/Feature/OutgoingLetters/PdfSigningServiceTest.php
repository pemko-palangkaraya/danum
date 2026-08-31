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

    public function test_existing_pdf_can_be_signed_with_pades_b_b_is_covered_by_live_smoke_test(): void
    {
        $this->markTestSkipped('PAdES/TSA live signing is covered outside the default test suite.');
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
            $this->expectException(\\DomainException::class);
            app(PdfSigningService::class)->sign($sourcePath, $certificate, 'Penguji DANUM', 'Test');
        } finally {
            @unlink($sourcePath);
        }
    }

    private function minimalPdf(): string
    {
        if (! defined('K_PATH_FONTS')) {
            $fontPath = realpath(base_path('vendor/tecnickcom/tc-lib-pdf-font/target/fonts'));
            if ($fontPath !== false) define('K_PATH_FONTS', $fontPath);
        }

        $pdf = new \\Com\\Tecnick\\Pdf\\Tcpdf();
        $pdf->setCreator('DANUM Test');
        $pdf->setTitle('DANUM PDF signing test');
        $pdf->addPage(['format' => 'A4']);
        return $pdf->getOutPDFString();
    }
}
