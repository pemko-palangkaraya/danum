<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class LibreOfficeSpreadsheetService
{
    public function csvToXlsx(string $csvPath, string $outputDirectory, ?string $outputFilename = null): string
    {
        return $this->convert($csvPath, $outputDirectory, 'xlsx:Calc MS Excel 2007 XML', $outputFilename);
    }

    public function spreadsheetToCsv(string $spreadsheetPath, string $outputDirectory, ?string $outputFilename = null): string
    {
        if (! is_file($spreadsheetPath)) {
            throw new RuntimeException('File spreadsheet sumber tidak ditemukan.');
        }

        $extension = strtolower(pathinfo($spreadsheetPath, PATHINFO_EXTENSION));
        if (! in_array($extension, ['xlsx', 'xls', 'ods'], true)) {
            throw new RuntimeException('Format spreadsheet tidak didukung. Gunakan XLSX, XLS, atau ODS.');
        }

        return $this->convert($spreadsheetPath, $outputDirectory, 'csv:Text - txt - csv (StarCalc)', $outputFilename);
    }

    private function convert(string $sourcePath, string $outputDirectory, string $filter, ?string $outputFilename = null): string
    {
        if (! is_file($sourcePath)) {
            throw new RuntimeException('File sumber tidak ditemukan.');
        }

        if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0777, true) && ! is_dir($outputDirectory)) {
            throw new RuntimeException('Direktori output spreadsheet tidak dapat dibuat.');
        }

        $binary = (string) (config('services.libreoffice.binary') ?: 'soffice');
        $profile = 'file:///' . str_replace('\\', '/', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'danum-lo-' . bin2hex(random_bytes(8)));

        $process = new Process([
            $binary,
            '--headless',
            '--nologo',
            '--nodefault',
            '--nolockcheck',
            '--nofirststartwizard',
            '-env:UserInstallation=' . $profile,
            '--convert-to',
            $filter,
            '--outdir',
            $outputDirectory,
            $sourcePath,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $detail = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException('LibreOffice gagal mengonversi spreadsheet: ' . ($detail !== '' ? $detail : 'proses berhenti tanpa pesan error.'));
        }

        $sourceName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $extension = str_starts_with($filter, 'xlsx:') ? 'xlsx' : 'csv';
        $generated = rtrim($outputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sourceName . '.' . $extension;

        if (! is_file($generated)) {
            throw new RuntimeException('LibreOffice tidak menghasilkan file ' . strtoupper($extension) . '. Output: ' . trim($process->getOutput()));
        }

        if ($outputFilename === null || basename($generated) === basename($outputFilename)) {
            return $generated;
        }

        $target = rtrim($outputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($outputFilename);
        if (! rename($generated, $target)) {
            throw new RuntimeException('File hasil konversi tidak dapat dipindahkan.');
        }

        return $target;
    }
}
