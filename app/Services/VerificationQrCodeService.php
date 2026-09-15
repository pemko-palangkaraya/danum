<?php

declare(strict_types=1);

namespace App\Services;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use RuntimeException;

class VerificationQrCodeService
{
    private const PREVIEW_QR_PAYLOAD = 'Ini masih preview surat';
    private const PREVIEW_QR_RED = [185, 28, 28];

    public function render(string $verificationUrl): string
    {
        return $this->renderQr($verificationUrl);
    }

    public function renderPreview(): string
    {
        $png = $this->renderQr(self::PREVIEW_QR_PAYLOAD);
        return $this->recolorDarkPixels($png, self::PREVIEW_QR_RED);
    }

    private function renderQr(string $payload): string
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

        return (new QRCode($options))->render($payload);
    }

    /**
     * Change only the dark QR modules while preserving the white quiet zone.
     * The result remains a normal PNG and can still be scanned reliably.
     *
     * @param array{0:int,1:int,2:int} $rgb
     */
    private function recolorDarkPixels(string $png, array $rgb): string
    {
        $prefix = 'data:image/png;base64,';
        if (! str_starts_with($png, $prefix)) {
            throw new RuntimeException('QR preview tidak menghasilkan PNG yang valid.');
        }

        $bytes = base64_decode(substr($png, strlen($prefix)), true);
        if ($bytes === false) {
            throw new RuntimeException('Data QR preview tidak valid.');
        }

        $image = imagecreatefromstring($bytes);
        if ($image === false) {
            throw new RuntimeException('QR preview tidak dapat diproses sebagai gambar.');
        }

        $red = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        $width = imagesx($image);
        $height = imagesy($image);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                if ($color['red'] < 180 && $color['green'] < 180 && $color['blue'] < 180) {
                    imagesetpixel($image, $x, $y, $red);
                }
            }
        }

        ob_start();
        imagepng($image);
        $result = ob_get_clean();
        imagedestroy($image);

        if ($result === false) {
            throw new RuntimeException('QR preview gagal dikonversi menjadi PNG.');
        }

        return $prefix . base64_encode($result);
    }
}
