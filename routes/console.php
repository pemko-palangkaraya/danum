<?php

use App\Services\CertificateAuthorityService;
use App\Services\LetterTypeService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('danum:ca-init', function (CertificateAuthorityService $service): void {
    [$root, $issuing] = $service->ensureHierarchy();

    $this->info('DANUM CA hierarchy siap.');
    $this->line('Root CA:    '.$root->name.' | '.$root->fingerprint_sha256);
    $this->line('Issuing CA: '.$issuing->name.' | '.$issuing->fingerprint_sha256);
})->purpose('Initialize the DANUM Root CA and Issuing CA hierarchy');

Schedule::call(function (LetterTypeService $service): void {
    $service->processScheduledDeletions();
})
    ->name('danum-process-scheduled-letter-type-deletions')
    ->everySecond()
    ->between('23:59', '23:59:59')
    ->withoutOverlapping();
