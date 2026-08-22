<header class="sticky top-0 z-30 border-b border-slate-200 bg-white lg:hidden">
    <div class="flex h-16 items-center justify-between px-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center"><x-danum-logo class="h-8 w-auto text-yellow-400" /></a>

        <details class="relative">
            <summary class="flex cursor-pointer list-none items-center rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-100" aria-label="Buka menu navigasi">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </summary>

            <div class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
                <p class="px-3 pb-2 pt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Application</p>

                <a href="{{ route('dashboard') }}" @class(['flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('dashboard'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('dashboard')])>Dashboard</a>

                @if (auth()->user()?->isSuperAdmin())
                    <a href="{{ route('tenants.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('tenants.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenants.*')])>Tenants</a>
                    <a href="{{ route('users.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('users.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('users.*')])>Users</a>
                    <a href="{{ route('letter-types.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('letter-types.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('letter-types.*')])>Letter Types</a>
                @endif

                @if (auth()->user()?->isTenantUser())
                    <a href="{{ route('outgoing-letters.index') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('outgoing-letters.*'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('outgoing-letters.*')])>Outgoing Letters</a>
                    <a href="{{ route('tenant.profile') }}" @class(['mt-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-900 text-white'=>request()->routeIs('tenant.profile'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900'=>!request()->routeIs('tenant.profile')])>Profil Organisasi</a>
                @endif

                <div class="my-2 border-t border-slate-100"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Logout</button>
                </form>
            </div>
        </details>
    </div>
</header>
