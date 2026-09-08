<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$fontsDirectory = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'tc-lib-pdf-font' . DIRECTORY_SEPARATOR . 'core';
$fontFile = $fontsDirectory . DIRECTORY_SEPARATOR . 'helvetica.json';
$sourceFile = $fontsDirectory . DIRECTORY_SEPARATOR . 'Helvetica.afm';

if (is_file($fontFile)) {
    exit(0);
}

$autoload = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
$importClass = '\\Com\\Tecnick\\Pdf\\Font\\Import';

if (! is_file($autoload)) {
    fwrite(STDERR, "tc-lib-pdf-font belum tersedia; lewati inisialisasi font.\n");
    exit(0);
}

require_once $autoload;

if (! class_exists($importClass)) {
    fwrite(STDERR, "tc-lib-pdf-font tidak tersedia; lewati inisialisasi font.\n");
    exit(0);
}

if (! is_dir($fontsDirectory) && ! mkdir($fontsDirectory, 0755, true) && ! is_dir($fontsDirectory)) {
    fwrite(STDERR, "Tidak dapat membuat direktori font: {$fontsDirectory}\n");
    exit(1);
}

$sourceUrl = 'https://raw.githubusercontent.com/tecnickcom/tc-font-core14-afms/main/Helvetica.afm';
$context = stream_context_create([
    'http' => [
        'follow_location' => 1,
        'max_redirects' => 3,
        'timeout' => 30,
    ],
]);

$fontSource = @file_get_contents($sourceUrl, false, $context);
if ($fontSource === false || trim($fontSource) === '') {
    fwrite(STDERR, "Tidak dapat mengambil Core14 Helvetica AFM dari {$sourceUrl}\n");
    exit(1);
}

if (file_put_contents($sourceFile, $fontSource) === false) {
    fwrite(STDERR, "Tidak dapat menyimpan source font: {$sourceFile}\n");
    exit(1);
}

try {
    new $importClass(
        $sourceFile,
        $fontsDirectory . DIRECTORY_SEPARATOR,
        'Core',
        '',
        32,
        3,
        1,
        false,
    );
} catch (Throwable $exception) {
    @unlink($sourceFile);
    @unlink($fontFile);
    fwrite(STDERR, "Gagal membuat font tc-lib-pdf: {$exception->getMessage()}\n");
    exit(1);
}

@unlink($sourceFile);

if (! is_file($fontFile)) {
    fwrite(STDERR, "Font tc-lib-pdf tidak berhasil dibuat: {$fontFile}\n");
    exit(1);
}

fwrite(STDOUT, "tc-lib-pdf Helvetica font siap: {$fontFile}\n");
