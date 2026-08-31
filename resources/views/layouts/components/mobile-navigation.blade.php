<div class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
    <p class="px-3 pb-2 pt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Application</p>
    <a href="{{ route('dashboard') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('dashboard'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('dashboard')])>Dashboard</a>

    @if (auth()->user()?->isSuperAdmin())
        @foreach ([
            ['tenants.index', 'tenants.*', 'Tenants'],
            ['tenant-categories.index', 'tenant-categories.*', 'Kategori Tenant'],
            ['users.index', 'users.*', 'Users'],
            ['letter-types.index', 'letter-types.*', 'Letter Types'],
            ['positions.admin.index', 'positions.admin.*', 'Jabatan'],
            ['outgoing-letters.index', 'outgoing-letters.*', 'Outgoing Letters'],
            ['outgoing-letter-withdrawals.index', 'outgoing-letter-withdrawals.*', 'Penarikan Surat'],
            ['audit-logs.index', 'audit-logs.*', 'Audit Log'],
        ] as [$route, $activeRoute, $label])
            <a href="{{ route($route) }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs($activeRoute),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs($activeRoute)])>{{ $label }}</a>
        @endforeach
    @endif

    @if (auth()->user()?->isTenantUser())
        <a href="{{ route('positions.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('positions.index'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('positions.index')])>Jabatan</a>
        @if (auth()->user()?->isTenantAdmin())
            <a href="{{ route('tenant-users.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('tenant-users.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant-users.*')])>Tenant Users</a>
        @endif
        <a href="{{ route('outgoing-letters.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('outgoing-letters.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letters.*')])>Outgoing Letters</a>
        <a href="{{ route('outgoing-letter-withdrawals.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('outgoing-letter-withdrawals.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letter-withdrawals.*')])>Penarikan Surat</a>
    @endif

    <div class="my-2 border-t border-slate-100"></div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Logout</button>
    </form>
</div>
