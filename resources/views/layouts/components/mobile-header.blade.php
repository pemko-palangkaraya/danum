<header class="sticky top-0 z-30 border-b border-slate-200 bg-white lg:hidden">
    <div class="flex h-16 items-center justify-between px-4">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center"><x-danum-logo class="h-8 w-auto text-yellow-400" /></a>

        <details class="relative">
            <summary class="flex cursor-pointer list-none items-center rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-100" aria-label="Buka menu navigasi">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </summary>
            @include('layouts.components.mobile-navigation')
        </details>
    </div>
</header>
