<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocxPdfService
{
    public function convert(string $docxPath): string
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($docxPath)) {
            throw new RuntimeException('DOCX hasil surat tidak ditemukan.');
        }

        $binary = (string) config('services.libreoffice.binary', env('DANUM_LIBREOFFICE_BINARY', 'soffice'));
        $workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'danum-pdf-' . bin2hex(random_bytes(8));
        if (! mkdir($workDir, 0777, true) && ! is_dir($workDir)) {
            throw new RuntimeException('Tidak dapat membuat direktori sementara PDF.');
        }

        $source = $workDir . DIRECTORY_SEPARATOR . 'letter.docx';
        $pdf = $workDir . DIRECTORY_SEPARATOR . 'letter.pdf';
        file_put_contents($source, $disk->get($docxPath));

        $command = escapeshellarg($binary)
            . ' --headless --convert-to pdf --outdir ' . escapeshellarg($workDir)
            . ' ' . escapeshellarg($source);

        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0 || ! is_file($pdf)) {
            $message = trim(implode("\n", $output));
            $this->cleanup($workDir);
            throw new RuntimeException(
                $message !== ''
                    ? 'Konversi DOCX ke PDF gagal: ' . $message
                    : 'Konversi DOCX ke PDF gagal. Pastikan LibreOffice tersedia dan DANUM_LIBREOFFICE_BINARY sudah benar.'
            );
        }

        $target = 'outgoing-letters/' . date('Y/m') . '/' . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
        $disk->put($target, file_get_contents($pdf));
        $this->cleanup($workDir);

        return $target;
    }

    private function cleanup(string $directory): void
    {
        if (! is_dir($directory)) return;
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($directory);
    }
}
