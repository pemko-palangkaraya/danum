@php
    $user = auth()->user();
    $activeSection = match (true) {
        request()->routeIs('tenants.*', 'tenant-categories.*', 'users.*', 'positions.*', 'tenant-users.*', 'tenant-profile') => 'tenant',
        request()->routeIs('population.*') => 'population',
        request()->routeIs('letter-types.*', 'outgoing-letters.*') => 'letters',
        request()->routeIs('audit-logs.*') => 'monitoring',
        request()->routeIs('rbac.*') => 'administration',
        default => null,
    };
@endphp

<nav
    class="flex-1 space-y-1 px-4 py-6"
    x-data="{ openSection: @js($activeSection) }"
>
    <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Application</p>

    <a href="{{ route('dashboard') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('dashboard'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('dashboard')])>
        Dashboard
    </a>

    @if ($user?->isSuperAdmin())
        @if ($user?->hasPermission('tenants.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'tenant' ? null : 'tenant'" :aria-expanded="openSection === 'tenant'">
                    <span>Tenant</span>
                    <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openSection === 'tenant' }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
                </button>
                <div x-show="openSection === 'tenant'" x-collapse class="mt-1 space-y-1 pl-2">
                    <a href="{{ route('tenants.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('tenants.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenants.*')])>Tenants</a>
                    <a href="{{ route('tenant-categories.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('tenant-categories.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant-categories.*')])>Kategori Tenant</a>
                    @if ($user?->hasPermission('users.view'))<a href="{{ route('users.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('users.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('users.*')])>Users</a>@endif
                    @if ($user?->hasPermission('positions.view'))<a href="{{ route('positions.admin.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('positions.admin.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('positions.admin.*')])>Jabatan</a>@endif
                </div>
            </div>
        @endif
        @if ($user?->hasPermission('population.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'population' ? null : 'population'" :aria-expanded="openSection === 'population'"><span>Kependudukan</span><svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openSection === 'population' }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg></button>
                <div x-show="openSection === 'population'" x-collapse class="mt-1 space-y-1 pl-2">
                    <a href="{{ route('population.admin.citizens.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('population.admin.citizens.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('population.admin.citizens.*')])>Data Kependudukan</a>
                    <a href="{{ route('population.admin.families.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('population.admin.families.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('population.admin.families.*')])>Kartu Keluarga</a>
                    <a href="{{ route('population.admin.statistics') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('population.admin.statistics'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('population.admin.statistics')])>Statistik</a>
                </div>
            </div>
        @endif
        @if ($user?->hasPermission('letter-types.view') || $user?->hasPermission('outgoing-letters.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'letters' ? null : 'letters'" :aria-expanded="openSection === 'letters'"><span>Surat</span><svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openSection === 'letters' }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg></button>
                <div x-show="openSection === 'letters'" x-collapse class="mt-1 space-y-1 pl-2">
                    @if ($user?->hasPermission('letter-types.view'))<a href="{{ route('letter-types.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('letter-types.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('letter-types.*')])>Letter Types</a>@endif
                    @if ($user?->hasPermission('outgoing-letters.view'))<a href="{{ route('outgoing-letters.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('outgoing-letters.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letters.*')])>Outgoing Letters</a>@endif
                </div>
            </div>
        @endif
        @if ($user?->hasPermission('audit-logs.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'monitoring' ? null : 'monitoring'" :aria-expanded="openSection === 'monitoring'"><span>Monitoring</span><svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openSection === 'monitoring' }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg></button>
                <div x-show="openSection === 'monitoring'" x-collapse class="mt-1 space-y-1 pl-2"><a href="{{ route('audit-logs.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('audit-logs.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('audit-logs.*')])>Audit Log</a></div>
            </div>
        @endif
    @elseif ($user?->isTenantUser())
        @if ($user?->hasPermission('population.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'population' ? null : 'population'" :aria-expanded="openSection === 'population'"><span>Kependudukan</span><svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openSection === 'population' }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25-4.5a.75.75 0 0 1-1.08 1.04l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg></button>
                <div x-show="openSection === 'population'" x-collapse class="mt-1 space-y-1 pl-2">
                    <a href="{{ route('population.citizens.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('population.citizens.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('population.citizens.*')])>Data Kependudukan</a>
                    <a href="{{ route('population.families.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('population.families.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('population.families.*')])>Kartu Keluarga</a>
                    <a href="{{ route('population.statistics') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('population.statistics'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('population.statistics')])>Statistik</a>
                </div>
            </div>
        @endif
        @if ($user?->hasPermission('outgoing-letters.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'letters' ? null : 'letters'" :aria-expanded="openSection === 'letters'"><span>Surat</span><svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openSection === 'letters' }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25-4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg></button>
                <div x-show="openSection === 'letters'" x-collapse class="mt-1 space-y-1 pl-2"><a href="{{ route('outgoing-letters.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('outgoing-letters.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letters.*')])>Outgoing Letters</a></div>
            </div>
        @endif
        @if ($user?->hasPermission('positions.view') || $user?->hasPermission('tenant-users.view') || $user?->hasPermission('tenant-profile.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'tenant' ? null : 'tenant'" :aria-expanded="openSection === 'tenant'"><span>Tenant</span><svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openSection === 'tenant' }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg></button>
                <div x-show="openSection === 'tenant'" x-collapse class="mt-1 space-y-1 pl-2">
                    @if ($user?->hasPermission('positions.view'))<a href="{{ route('positions.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('positions.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('positions.*')])>Jabatan</a>@endif
                    @if ($user?->hasPermission('tenant-users.view'))<a href="{{ route('tenant-users.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('tenant-users.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant-users.*')])>Tenant Users</a>@endif
                    @if ($user?->hasPermission('tenant-profile.view'))<a href="{{ route('tenant-profile') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('tenant-profile'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant-profile')])>Profil Organisasi</a>@endif
                </div>
            </div>
        @endif
    @endif

    @if ($user?->hasPermission('rbac.view'))
        <div class="mt-3 border-t border-slate-100 pt-3">
            <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'administration' ? null : 'administration'" :aria-expanded="openSection === 'administration'"><span>Administration</span><svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openSection === 'administration' }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg></button>
            <div x-show="openSection === 'administration'" x-collapse class="mt-1 space-y-1 pl-2"><a href="{{ route('rbac.index') }}" @class(['flex items-center rounded-xl px-3 py-2 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('rbac.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('rbac.*')])>Role &amp; Access Control</a></div>
        </div>
    @endif
</nav>
