<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantProfileController;
use App\Http\Controllers\LetterTypeController;
use App\Http\Controllers\OutgoingLetterController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\PositionController;

Route::get('verify/{token}', [VerificationController::class, 'show'])
    ->name('api.verification.show');

Route::middleware('auth')->group(function () {
    Route::apiResource('tenants', TenantController::class)->except(['create', 'edit']);
    Route::post('tenants/{id}/restore', [TenantController::class, 'restore']);
    Route::apiResource('users', UserController::class)->except(['create', 'edit']);
    Route::apiResource('letter-types', LetterTypeController::class)->except(['create', 'edit']);
    Route::get('letter-types/{id}/versions', [LetterTypeController::class, 'versions']);
    Route::post('letter-types/{id}/restore', [LetterTypeController::class, 'restore']);
    Route::apiResource('outgoing-letters', OutgoingLetterController::class)->except(['create', 'edit']);
    Route::post('outgoing-letters/preview', [OutgoingLetterController::class, 'preview']);
    Route::post('outgoing-letters/{id}/restore', [OutgoingLetterController::class, 'restore']);
    Route::post('outgoing-letters/{id}/validate', [OutgoingLetterController::class, 'validateLetter']);
    Route::post('outgoing-letters/{id}/issue', [OutgoingLetterController::class, 'issue']);
    Route::post('outgoing-letters/{id}/cancel', [OutgoingLetterController::class, 'cancel']);
    Route::get('outgoing-letters/{id}/pdf', [OutgoingLetterController::class, 'downloadPdf']);
    Route::get('outgoing-letters/{id}/history', [OutgoingLetterController::class, 'history']);
    Route::get('tenant/profile', [TenantProfileController::class, 'show'])->name('tenant.profile.show');
    Route::match(['put', 'patch'], 'tenant/profile', [TenantProfileController::class, 'update'])->name('tenant.profile.update');
    Route::apiResource('positions', PositionController::class)->except(['create', 'edit']);
    Route::post('positions/{id}/restore', [PositionController::class, 'restore']);
    Route::post('positions/{position}/holder', [PositionController::class, 'assignHolder']);
    Route::post('positions/{position}/holder/end', [PositionController::class, 'endHolder']);
    Route::get('positions/{position}/holder', [PositionController::class, 'activeHolder']);
    Route::get('positions/{position}/holders', [PositionController::class, 'holderHistory']);
});
