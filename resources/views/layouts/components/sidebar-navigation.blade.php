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

<nav class="flex-1 space-y-1 px-4 py-6" x-data="{ openSection: @js($activeSection) }">
    <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Application</p>

    <a
        href="{{ route('dashboard') }}"
        @class([
            'flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition',
            'bg-slate-900 text-white shadow-sm' => request()->routeIs('dashboard'),
            'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('dashboard'),
        ])
    >
        Dashboard
    </a>

    @if ($user?->isSuperAdmin())
        @if ($user?->hasPermission('tenants.view'))
            <div class="mt-3">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    @click="openSection = openSection === 'tenant' ? null : 'tenant'"
                    :aria-expanded="openSection === 'tenant'"
                    aria-controls="sidebar-tenant"
                >
                    <span>Tenant</span>
                    <span x-text="openSection === 'tenant' ? '−' : '+'" aria-hidden="true" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>

                <div id="sidebar-tenant" x-show="openSection === 'tenant'" x-cloak class="mt-1 space-y-1 pl-2">
                    <a href="{{ route('tenants.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Tenants</a>
                    <a href="{{ route('tenant-categories.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Kategori Tenant</a>
                    @if ($user?->hasPermission('users.view'))
                        <a href="{{ route('users.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Users</a>
                    @endif
                    @if ($user?->hasPermission('positions.view'))
                        <a href="{{ route('positions.admin.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Jabatan</a>
                        <a
                            href="{{ route('positions.structure.admin') }}"
                            @class([
                                'block rounded-xl px-3 py-2 text-sm font-medium transition',
                                'bg-slate-900 text-white shadow-sm' => request()->routeIs('positions.structure.*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('positions.structure.*'),
                            ])
                        >
                            Struktur Organisasi
                        </a>
                    @endif
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('population.view'))
            <div class="mt-3">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    @click="openSection = openSection === 'population' ? null : 'population'"
                    :aria-expanded="openSection === 'population'"
                    aria-controls="sidebar-population"
                >
                    <span>Kependudukan</span>
                    <span x-text="openSection === 'population' ? '−' : '+'" aria-hidden="true" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>

                <div id="sidebar-population" x-show="openSection === 'population'" x-cloak class="mt-1 space-y-1 pl-2">
                    <a href="{{ route('population.admin.citizens.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Data Kependudukan</a>
                    <a href="{{ route('population.admin.families.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Kartu Keluarga</a>
                    <a href="{{ route('population.admin.statistics') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Statistik</a>
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('letter-types.view') || $user?->hasPermission('outgoing-letters.view'))
            <div class="mt-3">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    @click="openSection = openSection === 'letters' ? null : 'letters'"
                    :aria-expanded="openSection === 'letters'"
                    aria-controls="sidebar-letters"
                >
                    <span>Surat</span>
                    <span x-text="openSection === 'letters' ? '−' : '+'" aria-hidden="true" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>

                <div id="sidebar-letters" x-show="openSection === 'letters'" x-cloak class="mt-1 space-y-1 pl-2">
                    @if ($user?->hasPermission('letter-types.view'))
                        <a href="{{ route('letter-types.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Letter Types</a>
                    @endif
                    @if ($user?->hasPermission('outgoing-letters.view'))
                        <a href="{{ route('outgoing-letters.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Outgoing Letters</a>
                    @endif
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('audit-logs.view'))
            <div class="mt-3">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    @click="openSection = openSection === 'monitoring' ? null : 'monitoring'"
                    :aria-expanded="openSection === 'monitoring'"
                    aria-controls="sidebar-monitoring"
                >
                    <span>Monitoring</span>
                    <span x-text="openSection === 'monitoring' ? '−' : '+'" aria-hidden="true" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>

                <div id="sidebar-monitoring" x-show="openSection === 'monitoring'" x-cloak class="mt-1 space-y-1 pl-2">
                    <a href="{{ route('audit-logs.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Audit Log</a>
                </div>
            </div>
        @endif
    @elseif ($user?->isTenantUser())
        @if ($user?->hasPermission('population.view'))
            <div class="mt-3">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    @click="openSection = openSection === 'population' ? null : 'population'"
                    :aria-expanded="openSection === 'population'"
                    aria-controls="sidebar-population"
                >
                    <span>Kependudukan</span>
                    <span x-text="openSection === 'population' ? '−' : '+'" aria-hidden="true" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>

                <div id="sidebar-population" x-show="openSection === 'population'" x-cloak class="mt-1 space-y-1 pl-2">
                    <a href="{{ route('population.citizens.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Data Kependudukan</a>
                    <a href="{{ route('population.families.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Kartu Keluarga</a>
                    <a href="{{ route('population.statistics') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Statistik</a>
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('outgoing-letters.view'))
            <div class="mt-3">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    @click="openSection = openSection === 'letters' ? null : 'letters'"
                    :aria-expanded="openSection === 'letters'"
                    aria-controls="sidebar-letters"
                >
                    <span>Surat</span>
                    <span x-text="openSection === 'letters' ? '−' : '+'" aria-hidden="true" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>

                <div id="sidebar-letters" x-show="openSection === 'letters'" x-cloak class="mt-1 space-y-1 pl-2">
                    <a href="{{ route('outgoing-letters.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Outgoing Letters</a>
                </div>
            </div>
        @endif

        @if ($user?->hasPermission('positions.view') || $user?->hasPermission('tenant-users.view') || $user?->hasPermission('tenant-profile.view'))
            <div class="mt-3">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                    @click="openSection = openSection === 'tenant' ? null : 'tenant'"
                    :aria-expanded="openSection === 'tenant'"
                    aria-controls="sidebar-tenant"
                >
                    <span>Tenant</span>
                    <span x-text="openSection === 'tenant' ? '−' : '+'" aria-hidden="true" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
                </button>

                <div id="sidebar-tenant" x-show="openSection === 'tenant'" x-cloak class="mt-1 space-y-1 pl-2">
                    @if ($user?->hasPermission('positions.view'))
                        <a href="{{ route('positions.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Jabatan</a>
                        <a
                            href="{{ route('positions.structure') }}"
                            @class([
                                'block rounded-xl px-3 py-2 text-sm font-medium transition',
                                'bg-slate-900 text-white shadow-sm' => request()->routeIs('positions.structure.*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('positions.structure.*'),
                            ])
                        >
                            Struktur Organisasi
                        </a>
                    @endif
                    @if ($user?->hasPermission('tenant-users.view'))
                        <a href="{{ route('tenant-users.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Tenant Users</a>
                    @endif
                    @if ($user?->hasPermission('tenant-profile.view'))
                        <a href="{{ route('tenant-profile') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Profil Organisasi</a>
                    @endif
                </div>
            </div>
        @endif
    @endif

    @if ($user?->hasPermission('rbac.view'))
        <div class="mt-3 border-t border-slate-100 pt-3">
            <button
                type="button"
                class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                @click="openSection = openSection === 'administration' ? null : 'administration'"
                :aria-expanded="openSection === 'administration'"
                aria-controls="sidebar-administration"
            >
                <span>Administration</span>
                <span x-text="openSection === 'administration' ? '−' : '+'" aria-hidden="true" class="ml-3 shrink-0 text-base font-normal leading-none text-slate-400"></span>
            </button>

            <div id="sidebar-administration" x-show="openSection === 'administration'" x-cloak class="mt-1 space-y-1 pl-2">
                <a href="{{ route('rbac.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Role &amp; Access Control</a>
            </div>
        </div>
    @endif
</nav>
