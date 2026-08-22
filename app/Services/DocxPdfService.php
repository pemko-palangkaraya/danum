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
        $isAbsolute = is_file($docxPath);
        if (! $isAbsolute && ! $disk->exists($docxPath)) {
            throw new RuntimeException('DOCX hasil surat tidak ditemukan.');
        }

        $binary = $this->resolveBinary();
        $workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'danum-pdf-' . bin2hex(random_bytes(8));
        if (! mkdir($workDir, 0777, true) && ! is_dir($workDir)) {
            throw new RuntimeException('Tidak dapat membuat direktori sementara PDF.');
        }

        $source = $workDir . DIRECTORY_SEPARATOR . 'letter.docx';
        $pdf = $workDir . DIRECTORY_SEPARATOR . 'letter.pdf';
        $contents = $isAbsolute ? file_get_contents($docxPath) : $disk->get($docxPath);
        if ($contents === false) {
            $this->cleanup($workDir);
            throw new RuntimeException('DOCX hasil surat tidak dapat dibaca.');
        }
        file_put_contents($source, $contents);

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

    private function resolveBinary(): string
    {
        $configured = (string) config('services.libreoffice.binary', '');
        if ($configured !== '' && $this->isExecutablePath($configured)) return $configured;

        $candidates = PHP_OS_FAMILY === 'Windows'
            ? [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
                getenv('PROGRAMFILES') . '\\LibreOffice\\program\\soffice.exe',
                getenv('PROGRAMFILES(X86)') . '\\LibreOffice\\program\\soffice.exe',
            ]
            : ['/usr/bin/soffice', '/usr/local/bin/soffice', '/snap/bin/libreoffice'];

        foreach (array_unique(array_filter($candidates)) as $candidate) if ($this->isExecutablePath($candidate)) return $candidate;

        $command = PHP_OS_FAMILY === 'Windows' ? 'where soffice 2>NUL' : 'command -v soffice 2>/dev/null';
        $resolved = trim((string) shell_exec($command));
        if ($resolved !== '') {
            $resolved = preg_split('/\r?\n/', $resolved)[0] ?? $resolved;
            if ($this->isExecutablePath($resolved) || PHP_OS_FAMILY !== 'Windows') return $resolved;
        }

        throw new RuntimeException('LibreOffice belum ditemukan. Install LibreOffice terlebih dahulu, atau isi DANUM_LIBREOFFICE_BINARY di file .env dengan path ke soffice.exe. Contoh: C:\\Program Files\\LibreOffice\\program\\soffice.exe');
    }

    private function isExecutablePath(string $path): bool
    {
        return is_file($path) && is_readable($path);
    }

    private function cleanup(string $directory): void
    {
        if (! is_dir($directory)) return;
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) @unlink($file);
        @rmdir($directory);
    }
}
