<?php

use App\Services\CertificateAuthorityService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('danum:ca-init', function (CertificateAuthorityService $service): void {
    [$root, $issuing] = $service->ensureHierarchy();

    $this->info('DANUM CA hierarchy siap.');
    $this->line('Root CA:    '.$root->name.' | '.$root->fingerprint_sha256);
    $this->line('Issuing CA: '.$issuing->name.' | '.$issuing->fingerprint_sha256);
})->purpose('Initialize the DANUM Root CA and Issuing CA hierarchy');
