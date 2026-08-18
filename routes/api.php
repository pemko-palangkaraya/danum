<?php

use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantProfileController;

Route::apiResource('tenants', TenantController::class)
    ->except(['create', 'edit']);

Route::post(
    'tenants/{id}/restore',
    [TenantController::class, 'restore'],
);

Route::apiResource('users', UserController::class)
    ->except(['create', 'edit']);

Route::middleware('auth')->group(function () {
    Route::get('tenant/profile', [TenantProfileController::class, 'show'])
        ->name('tenant.profile.show');

    Route::match(['put', 'patch'], 'tenant/profile', [TenantProfileController::class, 'update'])
        ->name('tenant.profile.update');
});
