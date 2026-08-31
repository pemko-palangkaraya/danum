<div class="border-t border-slate-100 p-4">
    @if (auth()->user()?->isTenantUser() && auth()->user()?->hasPermission('outgoing-letters.issue'))
        <a href="{{ route('settings.signing-certificate') }}" @class(['mb-3 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-100 text-slate-900' => request()->routeIs('settings.signing-certificate'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('settings.signing-certificate')])>
            <svg class="mr-3 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h10A1.5 1.5 0 0 1 18.5 5v14A1.5 1.5 0 0 1 17 20.5H7A1.5 1.5 0 0 1 5.5 19V5A1.5 1.5 0 0 1 7 3.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M8.5 8h7M8.5 12h7M8.5 16h4" /></svg>
            Sertifikat TTE
        </a>
        <a href="{{ route('settings.signing-pin') }}" @class(['mb-3 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition','bg-slate-100 text-slate-900' => request()->routeIs('settings.signing-pin'),'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('settings.signing-pin')])>
            <svg class="mr-3 h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8.5a4 4 0 1 0-5.66 5.66L13 17.33V21h3v-2h2v-2h2v-3.67l-4.5-4.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 11.5h.01" /></svg>
            PIN Tanda Tangan
        </a>
    @endif

    <div class="mb-3 rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
        <div class="font-semibold text-slate-700">Waktu Server</div>
        <div class="mt-0.5 font-mono text-sm text-slate-900" data-server-clock data-server-timestamp="{{ now()->getTimestampMs() }}" data-server-timezone="{{ config('app.timezone') }}">{{ now()->format('d M Y, H:i:s') }}</div>
        <div class="mt-0.5 text-[11px] text-slate-400">{{ config('app.timezone') }}</div>
    </div>
    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Logout</button></form>
</div>
