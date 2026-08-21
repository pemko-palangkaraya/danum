<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages.auth.login')
        ->name('login');

    Route::livewire('/register', 'pages.auth.register')
        ->name('register');

    Route::view('/forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Route::view('/reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    // Route::view('/dashboard', 'dashboard')
    //     ->name('dashboard');

    Route::livewire('/dashboard', 'pages.dashboard')
        ->name('dashboard');

    Route::post('/logout', function (Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
