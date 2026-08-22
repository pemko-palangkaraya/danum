<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use setasign\Fpdi\Fpdi;

class PdfPreviewWatermarkService
{
    public function apply(string $pdfPath, string $label): string
    {
        if (! is_file($pdfPath)) {
            throw new RuntimeException('PDF preview tidak ditemukan.');
        }

        $output = tempnam(sys_get_temp_dir(), 'danum-preview-');
        if ($output === false) {
            throw new RuntimeException('Tidak dapat membuat file PDF preview sementara.');
        }
        $output .= '.pdf';

        $pdf = new class extends Fpdi {
            private float $angle = 0.0;

            private function outputCommand(string $command): void
            {
                $this->_out($command);
            }

            public function rotate(float $angle, float $x = -1, float $y = -1): void
            {
                if ($x < 0) {
                    $x = $this->GetX();
                }
                if ($y < 0) {
                    $y = $this->GetY();
                }
                if ($this->angle !== 0.0) {
                    $this->outputCommand('Q');
                }
                $this->angle = $angle;
                if ($angle !== 0.0) {
                    $rad = $angle * M_PI / 180;
                    $c = cos($rad);
                    $s = sin($rad);
                    $cx = $x * $this->k;
                    $cy = ($this->h - $y) * $this->k;
                    $this->outputCommand(sprintf(
                        'q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm',
                        $c,
                        $s,
                        -$s,
                        $c,
                        $cx,
                        $cy,
                        -$cx,
                        -$cy
                    ));
                }
            }

            public function closeRotation(): void
            {
                if ($this->angle !== 0.0) {
                    $this->outputCommand('Q');
                    $this->angle = 0.0;
                }
            }
        };

        $pageCount = $pdf->setSourceFile($pdfPath);
        $fontSize = 13;

        // Watermark must never trigger FPDF's automatic page-break mechanism.
        $pdf->SetAutoPageBreak(false);

        for ($page = 1; $page <= $pageCount; $page++) {
            $template = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($template);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($template);

            $pdf->SetFont('Helvetica', 'B', $fontSize);
            $pdf->SetTextColor(175, 175, 175);
            $pdf->SetLineWidth(0.1);

            // Keep every watermark anchor inside the actual page bounds.
            // A small negative margin is avoided because FPDF may interpret it
            // as a page-break trigger when drawing a Cell.
            $stepX = max(58.0, $size['width'] / 3.0);
            $stepY = max(42.0, $size['height'] / 5.0);

            for ($y = 12.0; $y <= $size['height'] - 12.0; $y += $stepY) {
                for ($x = 8.0; $x <= $size['width'] - 45.0; $x += $stepX) {
                    $pdf->rotate(-32, $x, $y);
                    $pdf->SetXY($x, $y);
                    $pdf->Cell(45, 6, $label, 0, 0, 'L');
                    $pdf->rotate(0);
                }
            }

            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->Output('F', $output);

        if (! is_file($output)) {
            throw new RuntimeException('Gagal membuat PDF preview dengan watermark.');
        }

        return $output;
    }
}
