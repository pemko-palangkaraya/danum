<div class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
    <p class="px-3 pb-2 pt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Application</p>
    <a href="{{ route('dashboard') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('dashboard'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('dashboard')])>Dashboard</a>

    @if (auth()->user()?->isSuperAdmin())
        @foreach ([
            ['tenants.view', 'tenants.index', 'tenants.*', 'Tenants'],
            ['tenants.view', 'tenant-categories.index', 'tenant-categories.*', 'Kategori Tenant'],
            ['users.view', 'users.index', 'users.*', 'Users'],
            ['letter-types.view', 'letter-types.index', 'letter-types.*', 'Letter Types'],
            ['positions.view', 'positions.admin.index', 'positions.admin.*', 'Jabatan'],
            ['outgoing-letters.view', 'outgoing-letters.index', 'outgoing-letters.*', 'Outgoing Letters'],
            ['outgoing-letters.view', 'outgoing-letter-withdrawals.index', 'outgoing-letter-withdrawals.*', 'Penarikan Surat'],
            ['audit-logs.view', 'audit-logs.index', 'audit-logs.*', 'Audit Log'],
        ] as [$permission, $route, $activeRoute, $label])
            @if (auth()->user()?->hasPermission($permission))
                <a href="{{ route($route) }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs($activeRoute),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs($activeRoute)])>{{ $label }}</a>
            @endif
        @endforeach
    @endif

    @if (auth()->user()?->isTenantUser())
        @if (auth()->user()?->hasPermission('population.view'))
            <a href="{{ route('population.citizens.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('population.citizens.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('population.citizens.*')])>Data Kependudukan</a>
            <a href="{{ route('population.families.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('population.families.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('population.families.*')])>Kartu Keluarga</a>
            <a href="{{ route('population.statistics') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('population.statistics'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('population.statistics')])>Statistik</a>
        @endif
        @if (auth()->user()?->hasPermission('positions.view'))
            <a href="{{ route('positions.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('positions.index'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('positions.index')])>Jabatan</a>
        @endif
        @if (auth()->user()?->hasPermission('tenant-users.view'))
            <a href="{{ route('tenant-users.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('tenant-users.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant-users.*')])>Tenant Users</a>
        @endif
        @if (auth()->user()?->hasPermission('outgoing-letters.view'))
            <a href="{{ route('outgoing-letters.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('outgoing-letters.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letters.*')])>Outgoing Letters</a>
            <a href="{{ route('outgoing-letter-withdrawals.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('outgoing-letter-withdrawals.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letter-withdrawals.*')])>Penarikan Surat</a>
        @endif
        @if (auth()->user()?->hasPermission('tenant-profile.view'))
            <a href="{{ route('tenant-profile') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('tenant-profile'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant-profile')])>Profil Organisasi</a>
        @endif
    @endif

    @if (auth()->user()?->hasPermission('rbac.view'))
        <a href="{{ route('rbac.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('rbac.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('rbac.*')])>Role &amp; Access Control</a>
    @endif

    <div class="my-2 border-t border-slate-100"></div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Logout</button>
    </form>
</div>
