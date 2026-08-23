<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantProfileController;
use App\Http\Controllers\LetterTypeController;
use App\Http\Controllers\OutgoingLetterController;
use App\Http\Controllers\OutgoingLetterWithdrawalController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\PositionController;

Route::get('verify/{token}', [VerificationController::class, 'show'])->name('api.verification.show');

Route::middleware('auth')->as('api.')->group(function () {
    Route::apiResource('tenants', TenantController::class)->except(['create', 'edit']);
    Route::post('tenants/{id}/restore', [TenantController::class, 'restore'])->name('tenants.restore');
    Route::apiResource('users', UserController::class)->except(['create', 'edit']);
    Route::apiResource('letter-types', LetterTypeController::class)->except(['create', 'edit']);
    Route::get('letter-types/{id}/versions', [LetterTypeController::class, 'versions'])->name('letter-types.versions');
    Route::post('letter-types/{id}/restore', [LetterTypeController::class, 'restore'])->name('letter-types.restore');
    Route::apiResource('outgoing-letters', OutgoingLetterController::class)->except(['create', 'edit']);
    Route::post('outgoing-letters/preview', [OutgoingLetterController::class, 'preview'])->name('outgoing-letters.preview');
    Route::post('outgoing-letters/{id}/restore', [OutgoingLetterController::class, 'restore'])->name('outgoing-letters.restore');
    Route::post('outgoing-letters/{id}/submit', [OutgoingLetterController::class, 'submit'])->name('outgoing-letters.submit');
    Route::post('outgoing-letters/{id}/validate', [OutgoingLetterController::class, 'validateLetter'])->name('outgoing-letters.validate');
    Route::post('outgoing-letters/{id}/reject', [OutgoingLetterController::class, 'reject'])->name('outgoing-letters.reject');
    Route::post('outgoing-letters/{id}/issue', [OutgoingLetterController::class, 'issue'])->name('outgoing-letters.issue');
    Route::post('outgoing-letters/{id}/cancel', [OutgoingLetterController::class, 'cancel'])->name('outgoing-letters.cancel');
    Route::post('outgoing-letters/{id}/withdraw', [OutgoingLetterWithdrawalController::class, 'store'])->name('outgoing-letters.withdraw');
    Route::post('outgoing-letter-withdrawals/{request}/approve', [OutgoingLetterWithdrawalController::class, 'approve'])->name('outgoing-letter-withdrawals.approve');
    Route::post('outgoing-letter-withdrawals/{request}/reject', [OutgoingLetterWithdrawalController::class, 'reject'])->name('outgoing-letter-withdrawals.reject');
    Route::get('outgoing-letters/{id}/pdf', [OutgoingLetterController::class, 'downloadPdf'])->name('outgoing-letters.pdf');
    Route::get('outgoing-letters/{id}/history', [OutgoingLetterController::class, 'history'])->name('outgoing-letters.history');
    Route::get('tenant/profile', [TenantProfileController::class, 'show'])->name('tenant.profile.show');
    Route::match(['put', 'patch'], 'tenant/profile', [TenantProfileController::class, 'update'])->name('tenant.profile.update');
    Route::apiResource('positions', PositionController::class)->except(['create', 'edit']);
    Route::post('positions/{id}/restore', [PositionController::class, 'restore'])->name('positions.restore');
    Route::post('positions/{position}/holder', [PositionController::class, 'assignHolder'])->name('positions.holder.assign');
    Route::post('positions/{position}/holder/end', [PositionController::class, 'endHolder'])->name('positions.holder.end');
    Route::get('positions/{position}/holder', [PositionController::class, 'activeHolder'])->name('positions.holder.active');
    Route::get('positions/{position}/holders', [PositionController::class, 'holderHistory'])->name('positions.holder.history');
});
