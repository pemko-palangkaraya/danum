<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Livewire\Tenants\Index as TenantIndex;
use App\Livewire\LetterTypes\Index as LetterTypeIndex;
use App\Livewire\OutgoingLetters\Index as OutgoingLetterIndex;
use App\Livewire\OutgoingLetters\Show as OutgoingLetterShow;
use App\Livewire\Positions\Index as PositionIndex;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\OutgoingLetterController;

Route::view('/', 'welcome')->name('home');
Route::get('/verify/{token}', [VerificationController::class, 'page'])->name('verification.show');

Route::middleware('guest')->group(function () {
    Volt::route('/login', 'pages.auth.login')->name('login');
    Volt::route('/register', 'pages.auth.register')->name('register');
    Route::view('/forgot-password', 'pages.auth.forgot-password')->name('password.request');
    Route::view('/reset-password/{token}', 'pages.auth.reset-password')->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('/dashboard', 'pages.dashboard')->name('dashboard');

    Route::middleware('superadmin')->group(function () {
        Volt::route('/users', 'pages.users.index')->name('users.index');
        Route::get('/tenants', TenantIndex::class)->name('tenants.index');
        Volt::route('/tenants/create', 'pages.tenants.create')->name('tenants.create');
        Volt::route('/tenants/{tenant}/edit', 'pages.tenants.edit')->name('tenants.edit');
        Volt::route('/tenants/{tenant}/users', 'pages.tenants.users')->name('tenants.users');
        Volt::route('/tenants/{tenant}', 'pages.tenants.show')->name('tenants.show');
        Route::get('/letter-types', LetterTypeIndex::class)->name('letter-types.index');
    });

    Route::middleware('tenant')->group(function () {
        Route::get('/positions', PositionIndex::class)->name('positions.index');
    });

    Route::get('/outgoing-letters', OutgoingLetterIndex::class)->name('outgoing-letters.index');
    Route::get('/outgoing-letters/{id}/pdf', [OutgoingLetterController::class, 'downloadPdf'])->name('outgoing-letters.pdf');
    Route::get('/outgoing-letters/{id}', OutgoingLetterShow::class)->name('outgoing-letters.show');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');
});
