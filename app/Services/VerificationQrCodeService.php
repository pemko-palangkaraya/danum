<?php

declare(strict_types=1);

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class VerificationQrCodeService
{
    public function render(string $verificationUrl): string
    {
        $options = new QROptions([
            'eccLevel' => 'M',
            'outputBase64' => true,
            'svgAddXmlHeader' => false,
            'quietzoneSize' => 4,
        ]);

        return (new QRCode($options))->render($verificationUrl);
    }
}
