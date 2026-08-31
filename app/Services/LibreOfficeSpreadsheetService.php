<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class LibreOfficeSpreadsheetService
{
    public function csvToXlsx(string $csvPath, string $outputDirectory, ?string $outputFilename = null): string
    {
        if (! is_file($csvPath)) {
            throw new RuntimeException('File CSV sumber tidak ditemukan.');
        }

        if (! is_dir($outputDirectory) && ! mkdir($outputDirectory, 0777, true) && ! is_dir($outputDirectory)) {
            throw new RuntimeException('Direktori output spreadsheet tidak dapat dibuat.');
        }

        $binary = (string) (config('services.libreoffice.binary') ?: 'soffice');
        $process = new Process([
            $binary,
            '--headless',
            '--convert-to',
            'xlsx:Calc MS Excel 2007 XML',
            '--outdir',
            $outputDirectory,
            $csvPath,
        ]);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            $detail = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException('LibreOffice gagal mengonversi spreadsheet: ' . ($detail !== '' ? $detail : 'proses berhenti tanpa pesan error.'));
        }

        $sourceName = pathinfo($csvPath, PATHINFO_FILENAME);
        $generated = rtrim($outputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $sourceName . '.xlsx';

        if (! is_file($generated)) {
            throw new RuntimeException('LibreOffice tidak menghasilkan file XLSX. Output: ' . trim($process->getOutput()));
        }

        if ($outputFilename === null || basename($generated) === basename($outputFilename)) {
            return $generated;
        }

        $target = rtrim($outputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($outputFilename);
        if (! rename($generated, $target)) {
            throw new RuntimeException('File XLSX hasil konversi tidak dapat dipindahkan.');
        }

        return $target;
    }
}
