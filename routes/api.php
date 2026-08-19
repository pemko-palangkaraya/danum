<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantProfileController;
use App\Http\Controllers\LetterTypeController;
use App\Http\Controllers\OutgoingLetterController;

Route::middleware('auth')->group(function () {
    Route::apiResource('tenants', TenantController::class)
        ->except(['create', 'edit']);

    Route::post(
        'tenants/{id}/restore',
        [TenantController::class, 'restore'],
    );

    Route::apiResource('users', UserController::class)
        ->except(['create', 'edit']);

    Route::apiResource('letter-types', LetterTypeController::class)
        ->except(['create', 'edit']);

    Route::post(
        'letter-types/{id}/restore',
        [LetterTypeController::class, 'restore'],
    );

    Route::apiResource('outgoing-letters', OutgoingLetterController::class)
        ->except(['create', 'edit']);

    Route::post(
        'outgoing-letters/{id}/restore',
        [OutgoingLetterController::class, 'restore'],
    );

    Route::post(
        'outgoing-letters/{id}/validate',
        [OutgoingLetterController::class, 'validateLetter'],
    );

    Route::post(
        'outgoing-letters/{id}/issue',
        [OutgoingLetterController::class, 'issue'],
    );

    Route::post(
        'outgoing-letters/{id}/cancel',
        [OutgoingLetterController::class, 'cancel'],
    );

    Route::get(
        'outgoing-letters/{id}/pdf',
        [OutgoingLetterController::class, 'downloadPdf'],
    );

    Route::get('tenant/profile', [TenantProfileController::class, 'show'])
        ->name('tenant.profile.show');

    Route::match(['put', 'patch'], 'tenant/profile', [TenantProfileController::class, 'update'])
        ->name('tenant.profile.update');
});
