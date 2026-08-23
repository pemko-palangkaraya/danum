<aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">
    <div class="flex h-20 items-center border-b border-slate-100 px-6"><a href="{{ route('dashboard') }}"><x-danum-logo class="h-9 w-auto text-yellow-400" /></a></div>
    <nav class="flex-1 space-y-1 px-4 py-6">
        <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Application</p>
        <a href="{{ route('dashboard') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('dashboard'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('dashboard')])>Dashboard</a>

        @if (auth()->user()?->isSuperAdmin())
            <a href="{{ route('tenants.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('tenants.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenants.*')])>Tenants</a>
            <a href="{{ route('users.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('users.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('users.*')])>Users</a>
            <a href="{{ route('letter-types.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('letter-types.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('letter-types.*')])>Letter Types</a>
            <a href="{{ route('positions.admin.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('positions.admin.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('positions.admin.*')])>Jabatan</a>
        @endif

        @if (auth()->user()?->isTenantUser())
            <a href="{{ route('positions.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('positions.index'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('positions.index')])>Jabatan</a>
            <a href="{{ route('outgoing-letters.index') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('outgoing-letters.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letters.*')])>Outgoing Letters</a>
            <a href="{{ route('tenant-profile') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white shadow-sm'=>request()->routeIs('tenant-profile'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant-profile')])>Profil Organisasi</a>
        @endif
    </nav>
    <div class="border-t border-slate-100 p-4"><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Logout</button></form></div>
</aside>
