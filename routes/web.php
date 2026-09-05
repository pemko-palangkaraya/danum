<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Livewire\Dashboard;
use App\Livewire\TenantProfile;
use App\Livewire\AuditLogs\Index as AuditLogIndex;
use App\Livewire\Settings\Password as SettingsPassword;
use App\Livewire\Tenants\Index as TenantIndex;
use App\Livewire\Tenants\Create as TenantCreate;
use App\Livewire\Tenants\Edit as TenantEdit;
use App\Livewire\LetterTypes\Index as LetterTypeIndex;
use App\Livewire\LetterTypes\Permissions as LetterTypePermissions;
use App\Livewire\LetterTypes\Versions as LetterTypeVersions;
use App\Livewire\OutgoingLetters\Index as OutgoingLetterIndex;
use App\Livewire\OutgoingLetters\Show as OutgoingLetterShow;
use App\Livewire\OutgoingLetterWithdrawals\Index as OutgoingLetterWithdrawalIndex;
use App\Livewire\Positions\Index as PositionIndex;
use App\Livewire\Positions\Structure as PositionStructure;
use App\Livewire\Population\Families as PopulationFamilies;
use App\Livewire\Population\Statistics as PopulationStatistics;
use App\Livewire\Population\Citizens as PopulationCitizens;
use App\Livewire\Population\CitizenImport as PopulationCitizenImport;
use App\Livewire\Population\CitizenShow as PopulationCitizenShow;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\OutgoingLetterController;
use App\Http\Controllers\OutgoingLetterWithdrawalController;
use App\Http\Controllers\PopulationExportController;
use App\Http\Controllers\FamilyCardController;
use App\Http\Controllers\OrganizationStructureController;
use App\Http\Controllers\PositionAppointmentDocumentController;

Route::view('/', 'welcome')->name('home');
Route::get('/verify/{token}', [VerificationController::class, 'page'])->name('verification.show');

Route::middleware('guest')->group(function () {
    Volt::route('/login', 'pages.auth.login')->name('login');
    Volt::route('/register', 'pages.auth.register')->name('register');
    Route::view('/forgot-password', 'pages.auth.forgot-password')->name('password.request');
    Route::view('/reset-password/{token}', 'pages.auth.reset-password')->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::get('/settings/password', SettingsPassword::class)->name('settings.password');
    Route::get('/population/citizens/import', PopulationCitizenImport::class)->name('population.citizens.import');
    Route::middleware('permission:dashboard.view')->group(function () { Route::get('/dashboard', Dashboard::class)->name('dashboard'); });
    Route::middleware('permission:rbac.view')->group(function () { Volt::route('/rbac', 'pages.rbac.index')->name('rbac.index'); });
    Route::middleware(['superadmin', 'permission:users.view'])->group(function () { Volt::route('/users', 'pages.users.index')->name('users.index'); });
    Route::middleware('superadmin')->group(function () {
        Volt::route('/tenant-categories', 'pages.tenant-categories.index')->name('tenant-categories.index');
        Route::middleware('permission:tenants.view')->group(function () {
            Route::get('/tenants', TenantIndex::class)->name('tenants.index');
            Route::get('/tenants/create', TenantCreate::class)->name('tenants.create');
            Route::get('/tenants/{tenant}/edit', TenantEdit::class)->name('tenants.edit');
            Volt::route('/tenants/{tenant}/users', 'pages.tenants.users')->name('tenants.users');
            Volt::route('/tenants/{tenant}', 'pages.tenants.show')->name('tenants.show');
        });
        Route::middleware('permission:letter-types.view')->group(function () { Route::get('/letter-types', LetterTypeIndex::class)->name('letter-types.index'); Route::get('/letter-types/{letterType}/permissions', LetterTypePermissions::class)->name('letter-types.permissions'); Route::get('/letter-types/{letterType}/versions', LetterTypeVersions::class)->name('letter-types.versions'); });
        Route::middleware('permission:positions.view')->group(function () { Route::get('/positions', PositionIndex::class)->name('positions.admin.index'); Route::get('/organization-structure', PositionStructure::class)->name('positions.structure.admin'); Route::get('/organization-structure/{tenant}', PositionStructure::class)->name('positions.structure.admin.tenant'); Route::get('/organization-structure/{tenant}/pdf', [OrganizationStructureController::class, 'pdf'])->name('positions.structure.pdf'); });
        Route::middleware('permission:population.view')->group(function () { Route::get('/population/citizens', PopulationCitizens::class)->name('population.admin.citizens.index'); Route::get('/population/citizens/{citizen}', PopulationCitizenShow::class)->name('population.admin.citizens.show'); Route::get('/population/families', PopulationFamilies::class)->name('population.admin.families.index'); Route::get('/population/statistics', PopulationStatistics::class)->name('population.admin.statistics'); });
        Route::middleware('permission:audit-logs.view')->group(function () { Route::get('/audit-logs', AuditLogIndex::class)->name('audit-logs.index'); });
    });
    Route::middleware('tenant')->group(function () {
        Route::middleware('permission:positions.view')->group(function () { Route::get('/tenant/positions', PositionIndex::class)->name('positions.index'); Route::get('/tenant/organization-structure', PositionStructure::class)->name('positions.structure'); Route::get('/tenant/organization-structure/pdf', [OrganizationStructureController::class, 'pdf'])->name('positions.structure.pdf.tenant'); });
        Route::middleware('permission:tenant-users.view')->group(function () { Volt::route('/tenant/users', 'pages.tenant-users')->name('tenant-users.index'); });
        Route::middleware('permission:population.view')->group(function () { Route::get('/tenant/population/citizens', PopulationCitizens::class)->name('population.citizens.index'); Route::get('/tenant/population/citizens/{citizen}', PopulationCitizenShow::class)->name('population.citizens.show'); Route::get('/tenant/population/families', PopulationFamilies::class)->name('population.families.index'); Route::get('/tenant/population/statistics', PopulationStatistics::class)->name('population.statistics'); });
        Route::middleware('permission:tenant-profile.view')->group(function () { Route::get('/tenant-profile', TenantProfile::class)->name('tenant-profile'); });
    });
    Route::middleware('permission:population.view')->group(function () { Route::get('/population/citizens/export', [PopulationExportController::class, 'citizens'])->name('population.citizens.export'); Route::get('/population/families/{id}/pdf', [FamilyCardController::class, 'pdf'])->name('population.families.pdf'); });
    Route::get('/population/citizens/template', [PopulationExportController::class, 'template'])->middleware('permission:population.manage')->name('population.citizens.template');
    Route::middleware('permission:positions.view')->group(function () { Route::get('/position-holders/{holder}/appointment-document', [PositionAppointmentDocumentController::class, 'show'])->name('positions.appointment-document'); });
    Route::middleware('permission:outgoing-letters.view')->group(function () { Route::get('/outgoing-letters', OutgoingLetterIndex::class)->name('outgoing-letters.index'); Route::get('/outgoing-letter-withdrawals/{letter?}', OutgoingLetterWithdrawalIndex::class)->name('outgoing-letter-withdrawals.index'); Route::get('/outgoing-letter-withdrawals/{id}/statement', [OutgoingLetterWithdrawalController::class, 'statement'])->name('outgoing-letter-withdrawals.statement'); Route::get('/outgoing-letters/{id}/pdf', [OutgoingLetterController::class, 'downloadPdf'])->name('outgoing-letters.pdf'); Route::get('/outgoing-letters/{id}', OutgoingLetterShow::class)->name('outgoing-letters.show'); });
    Route::middleware('permission:outgoing-letters.issue')->group(function () { Volt::route('/settings/signing-pin', 'pages.settings.signing-pin')->name('settings.signing-pin'); Volt::route('/settings/signing-certificate', 'pages.settings.signing-certificate')->name('settings.signing-certificate'); });
    Route::post('/logout', function (Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('login'); })->name('logout');
});
