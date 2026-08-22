<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Livewire\Tenants\Index;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome')->name('home');


/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Volt::route('/login', 'pages.auth.login')
        ->name('login');

    Volt::route('/register', 'pages.auth.register')
        ->name('register');

    Route::view('/forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Route::view('/reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Volt::route('/dashboard', 'pages.dashboard')
        ->name('dashboard');


    /*
|--------------------------------------------------------------------------
| Tenant Management
|--------------------------------------------------------------------------
*/

    Route::get('/tenants', Index::class)
        ->name('tenants.index');

    Volt::route('/tenants/create', 'pages.tenants.create')
        ->name('tenants.create');

    Volt::route('/tenants/{tenant}/edit', 'pages.tenants.edit')
        ->name('tenants.edit');

    Volt::route('/tenants/{tenant}', 'pages.tenants.show')
        ->name('tenants.show');


    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', function (Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
