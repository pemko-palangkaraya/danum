<aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">
    <div class="flex h-20 items-center border-b border-slate-100 px-6"><a href="{{ route('dashboard') }}"><x-danum-logo class="h-9 w-auto text-yellow-400" /></a></div>
    <nav class="flex-1 space-y-1 px-4 py-6">
        <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Application</p>
        <a href="{{ route('dashboard') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('dashboard'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('dashboard')])>Dashboard</a>

        @if (auth()->user()?->isSuperAdmin())
            @if (auth()->user()?->hasPermission('tenants.view'))
            <a href="{{ route('tenants.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('tenants.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenants.*')])>Tenants</a>
            @endif
            <a href="{{ route('tenant-categories.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('tenant-categories.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant-categories.*')])>Kategori Tenant</a>
            @if (auth()->user()?->hasPermission('users.view'))
            <a href="{{ route('users.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('users.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('users.*')])>Users</a>
            @endif
            @if (auth()->user()?->hasPermission('letter-types.view'))
            <a href="{{ route('letter-types.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('letter-types.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('letter-types.*')])>Letter Types</a>
            @endif
            @if (auth()->user()?->hasPermission('positions.view'))
            <a href="{{ route('positions.admin.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('positions.admin.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('positions.admin.*')])>Jabatan</a>
            @endif
            @if (auth()->user()?->hasPermission('outgoing-letters.view'))
            <a href="{{ route('outgoing-letters.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('outgoing-letters.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letters.*')])>Outgoing Letters</a>
            <a href="{{ route('outgoing-letter-withdrawals.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('outgoing-letter-withdrawals.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letter-withdrawals.*')])>Penarikan Surat</a>
            @endif
            @if (auth()->user()?->hasPermission('audit-logs.view'))
            <a href="{{ route('audit-logs.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('audit-logs.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('audit-logs.*')])>Audit Log</a>
            @endif
        @elseif (auth()->user()?->isTenantUser())
            @if (auth()->user()?->hasPermission('positions.view'))
            <a href="{{ route('positions.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('positions.index'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('positions.index')])>Jabatan</a>
            @endif
            @if (auth()->user()?->isTenantAdmin() && auth()->user()?->hasPermission('tenant-users.view'))
            <a href="{{ route('tenant-users.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('tenant-users.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant-users.*')])>Tenant Users</a>
            @endif
            @if (auth()->user()?->hasPermission('outgoing-letters.view'))
            <a href="{{ route('outgoing-letters.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('outgoing-letters.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letters.*')])>Outgoing Letters</a>
            <a href="{{ route('outgoing-letter-withdrawals.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('outgoing-letter-withdrawals.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letter-withdrawals.*')])>Penarikan Surat</a>
            @endif
            @if (auth()->user()?->hasPermission('tenant-profile.view'))
            <a href="{{ route('tenant-profile') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('tenant-profile'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant-profile')])>Profil Organisasi</a>
            @endif
        @endif

        @if (auth()->user()?->hasPermission('rbac.view'))
        <div class="mt-6 border-t border-slate-100 pt-4">
            <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Administration</p>
            <a href="{{ route('rbac.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('rbac.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('rbac.*')])>Role &amp; Access Control</a>
        </div>
        @endif
    </nav>
    <div class="border-t border-slate-100 p-4">
        <div class="mb-3 rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
            <div class="font-semibold text-slate-700">Waktu Server</div>
            <div class="mt-0.5 font-mono text-sm text-slate-900" data-server-clock data-server-timestamp="{{ now()->getTimestampMs() }}" data-server-timezone="{{ config('app.timezone') }}">{{ now()->format('d M Y, H:i:s') }}</div>
            <div class="mt-0.5 text-[11px] text-slate-400">{{ config('app.timezone') }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Logout</button></form>
    </div>
</aside>