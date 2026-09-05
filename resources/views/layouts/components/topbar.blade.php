@auth
<header class="hidden h-20 items-center justify-end border-b border-slate-200 bg-white px-8 lg:fixed lg:inset-y-0 lg:left-64 lg:right-0 lg:z-30 lg:flex">
    <details class="relative">
        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-2 py-1.5 transition hover:bg-slate-50">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </summary>

        <div class="absolute right-0 mt-2 w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
            <div class="border-b border-slate-100 px-3 pb-3 pt-2">
                <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                <p class="mt-1 truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
            </div>

            <div class="py-2">
                <a href="{{ route('settings.password') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                    <svg class="mr-3 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7a4.5 4.5 0 0 0-9 0v3.5M6 10.5h12A1.5 1.5 0 0 1 19.5 12v7A1.5 1.5 0 0 1 18 20.5H6A1.5 1.5 0 0 1 4.5 19v-7A1.5 1.5 0 0 1 6 10.5Z" />
                    </svg>
                    Ganti Password
                </a>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                    <svg class="mr-3 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4.5A1.5 1.5 0 0 1 21 4.5v15a1.5 1.5 0 0 1-1.5 1.5H15" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 8l4 4-4 4M14 12H3" />
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </details>
</header>
@endauth