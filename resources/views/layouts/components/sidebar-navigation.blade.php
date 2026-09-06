@php
    $user = auth()->user();

    $activeSection = match (true) {
        request()->routeIs('tenants.*', 'tenant-categories.*', 'users.*', 'positions.*', 'tenant-users.*', 'tenant-profile') => 'tenant',
        request()->routeIs('population.*') => 'population',
        request()->routeIs('letter-types.*', 'letter-classifications.*', 'letter-variable-definitions.*', 'outgoing-letters.*', 'outgoing-letter-withdrawals.*') => 'letters',
        request()->routeIs('audit-logs.*') => 'monitoring',
        request()->routeIs('settings.signing-certificate', 'settings.signing-pin') => 'security',
        request()->routeIs('rbac.*') => 'administration',
        default => null,
    };
@endphp

<nav class="flex-1 space-y-1 px-4 py-6" x-data="{ openSection: @js($activeSection) }">
    <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Application</p>

    <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-sidebar-link>

    @if ($user?->isSuperAdmin())
        @if ($user?->hasPermission('tenants.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'tenant' ? null : 'tenant'" :aria-expanded="openSection === 'tenant'">
                    <span>Tenant</span><span x-text="openSection === 'tenant' ? '−' : '+'" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>
                <div x-show="openSection === 'tenant'" x-cloak class="mt-1 space-y-1 pl-2">
                    <x-sidebar-link :href="route('tenants.index')" :active="request()->routeIs('tenants.*')">Tenants</x-sidebar-link>
                    <x-sidebar-link :href="route('tenant-categories.index')" :active="request()->routeIs('tenant-categories.*')">Kategori Tenant</x-sidebar-link>
                    @if ($user?->hasPermission('users.view'))
                        <x-sidebar-link :href="route('users.index')" :active="request()->routeIs('users.*')">Users</x-sidebar-link>
                    @endif
                    @if ($user?->hasPermission('positions.view'))
                        <x-sidebar-link :href="route('positions.admin.index')" :active="request()->routeIs('positions.admin.*')">Jabatan</x-sidebar-link>
                        <x-sidebar-link :href="route('positions.structure.admin')" :active="request()->routeIs('positions.structure.admin')">Struktur Organisasi</x-sidebar-link>
                    @endif
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('population.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'population' ? null : 'population'" :aria-expanded="openSection === 'population'">
                    <span>Kependudukan</span><span x-text="openSection === 'population' ? '−' : '+'" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>
                <div x-show="openSection === 'population'" x-cloak class="mt-1 space-y-1 pl-2">
                    <x-sidebar-link :href="route('population.admin.citizens.index')" :active="request()->routeIs('population.admin.citizens.*')">Data Kependudukan</x-sidebar-link>
                    <x-sidebar-link :href="route('population.admin.families.index')" :active="request()->routeIs('population.admin.families.*')">Kartu Keluarga</x-sidebar-link>
                    <x-sidebar-link :href="route('population.admin.statistics')" :active="request()->routeIs('population.admin.statistics')">Statistik</x-sidebar-link>
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('letter-types.view') || $user?->hasPermission('outgoing-letters.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'letters' ? null : 'letters'" :aria-expanded="openSection === 'letters'">
                    <span>Surat</span><span x-text="openSection === 'letters' ? '−' : '+'" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>
                <div x-show="openSection === 'letters'" x-cloak class="mt-1 space-y-1 pl-2">
                    @if ($user?->hasPermission('letter-types.view'))
                        <x-sidebar-link :href="route('letter-classifications.index')" :active="request()->routeIs('letter-classifications.*')">Klasifikasi Surat</x-sidebar-link>
                        <x-sidebar-link :href="route('letter-types.index')" :active="request()->routeIs('letter-types.*')">Jenis Surat</x-sidebar-link>
                        <x-sidebar-link :href="route('letter-variable-definitions.index')" :active="request()->routeIs('letter-variable-definitions.*')">Variabel Surat</x-sidebar-link>
                    @endif
                    @if ($user?->hasPermission('outgoing-letters.view'))
                        <x-sidebar-link :href="route('outgoing-letters.index')" :active="request()->routeIs('outgoing-letters.*')">Surat Keluar</x-sidebar-link>
                        <x-sidebar-link :href="route('outgoing-letter-withdrawals.index')" :active="request()->routeIs('outgoing-letter-withdrawals.*')">Penarikan Surat</x-sidebar-link>
                    @endif
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('audit-logs.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'monitoring' ? null : 'monitoring'" :aria-expanded="openSection === 'monitoring'">
                    <span>Monitoring</span><span x-text="openSection === 'monitoring' ? '−' : '+'" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>
                <div x-show="openSection === 'monitoring'" x-cloak class="mt-1 space-y-1 pl-2">
                    <x-sidebar-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')">Audit Log</x-sidebar-link>
                </div>
            </div>
        @endif
    @elseif ($user?->isTenantUser())
        @if ($user?->hasPermission('population.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'population' ? null : 'population'" :aria-expanded="openSection === 'population'">
                    <span>Kependudukan</span><span x-text="openSection === 'population' ? '−' : '+'" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>
                <div x-show="openSection === 'population'" x-cloak class="mt-1 space-y-1 pl-2">
                    <x-sidebar-link :href="route('population.citizens.index')" :active="request()->routeIs('population.citizens.*')">Data Kependudukan</x-sidebar-link>
                    <x-sidebar-link :href="route('population.families.index')" :active="request()->routeIs('population.families.*')">Kartu Keluarga</x-sidebar-link>
                    <x-sidebar-link :href="route('population.statistics')" :active="request()->routeIs('population.statistics')">Statistik</x-sidebar-link>
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('outgoing-letters.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'letters' ? null : 'letters'" :aria-expanded="openSection === 'letters'">
                    <span>Surat</span><span x-text="openSection === 'letters' ? '−' : '+'" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>
                <div x-show="openSection === 'letters'" x-cloak class="mt-1 space-y-1 pl-2">
                    <x-sidebar-link :href="route('outgoing-letters.index')" :active="request()->routeIs('outgoing-letters.*')">Surat Keluar</x-sidebar-link>
                    <x-sidebar-link :href="route('outgoing-letter-withdrawals.index')" :active="request()->routeIs('outgoing-letter-withdrawals.*')">Penarikan Surat</x-sidebar-link>
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('positions.view') || $user?->hasPermission('tenant-users.view') || $user?->hasPermission('tenant-profile.view'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'tenant' ? null : 'tenant'" :aria-expanded="openSection === 'tenant'">
                    <span>Tenant</span><span x-text="openSection === 'tenant' ? '−' : '+'" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>
                <div x-show="openSection === 'tenant'" x-cloak class="mt-1 space-y-1 pl-2">
                    @if ($user?->hasPermission('positions.view'))
                        <x-sidebar-link :href="route('positions.index')" :active="request()->routeIs('positions.index')">Jabatan</x-sidebar-link>
                        <x-sidebar-link :href="route('positions.structure')" :active="request()->routeIs('positions.structure')">Struktur Organisasi</x-sidebar-link>
                    @endif
                    @if ($user?->hasPermission('tenant-users.view'))
                        <x-sidebar-link :href="route('tenant-users.index')" :active="request()->routeIs('tenant-users.*')">Pengguna Tenant</x-sidebar-link>
                    @endif
                    @if ($user?->hasPermission('tenant-profile.view'))
                        <x-sidebar-link :href="route('tenant-profile')" :active="request()->routeIs('tenant-profile')">Profil Organisasi</x-sidebar-link>
                    @endif
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('outgoing-letters.issue'))
            <div class="mt-3">
                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" @click="openSection = openSection === 'security' ? null : 'security'" :aria-expanded="openSection === 'security'">
                    <span>Keamanan</span><span x-text="openSection === 'security' ? '−' : '+'" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>
                <div x-show="openSection === 'security'" x-cloak class="mt-1 space-y-1 pl-2">
                    <x-sidebar-link :href="route('settings.signing-certificate')" :active="request()->routeIs('settings.signing-certificate')">Sertifikat TTE</x-sidebar-link>
                    <x-sidebar-link :href="route('settings.signing-pin')" :active="request()->routeIs('settings.signing-pin')">PIN Tanda Tangan</x-sidebar-link>
                </div>
            </div>
        @endif
    @endif

    @if ($user?->hasPermission('rbac.view'))
        <div class="mt-3 border-t border-slate-100 pt-3"><x-sidebar-link :href="route('rbac.index')" :active="request()->routeIs('rbac.*')">Role &amp; Access Control</x-sidebar-link></div>
    @endif
</nav>
