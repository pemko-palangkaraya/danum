<?php

declare(strict_types=1);

namespace App\Services;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use RuntimeException;

class VerificationQrCodeService
{
    public function render(string $verificationUrl): string
    {
        // LibreOffice/PDF conversion is considerably more reliable with a
        // raster image than an SVG embedded in OOXML. Keep the QR generation
        // here as PNG so the same bytes can be embedded into DOCX and then
        // carried through the DOCX -> PDF conversion.
        if (! extension_loaded('gd')) {
            throw new RuntimeException('Extension PHP GD wajib diaktifkan untuk membuat QR TTE.');
        }

        $options = new QROptions([
            'eccLevel' => 'M',
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => true,
            'scale' => 10,
            'quietzoneSize' => 4,
        ]);

        return (new QRCode($options))->render($verificationUrl);
    }
}
