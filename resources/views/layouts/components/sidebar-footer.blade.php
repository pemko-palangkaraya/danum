@php
    $unreadNotifications = auth()->user()?->unreadNotifications()->count() ?? 0;
@endphp

<div class="border-t border-slate-100 p-4">
    <div class="mb-3 rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
        <div class="font-semibold text-slate-700">Waktu Server</div>
        <div class="mt-0.5 font-mono text-sm text-slate-900" data-server-clock data-server-timestamp="{{ now()->getTimestampMs() }}" data-server-timezone="{{ config('app.timezone') }}">{{ now()->format('d M Y, H:i:s') }}</div>
        <div class="mt-0.5 text-[11px] text-slate-400">{{ config('app.timezone') }}</div>
    </div>

    <a href="{{ route('notifications.index') }}" class="mb-2 flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
        <span class="flex items-center gap-3">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 18H9m9-3H6l1.3-1.7A4.5 4.5 0 0 0 8.2 10.6V9a3.8 3.8 0 1 1 7.6 0v1.6a4.5 4.5 0 0 0 .9 2.7L18 15Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 18a2 2 0 0 0 4 0" />
            </svg>
            Notifikasi
        </span>
        @if ($unreadNotifications > 0)
            <span class="min-w-5 rounded-full bg-rose-600 px-1.5 py-0.5 text-center text-[11px] font-bold leading-4 text-white">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
        @endif
    </a>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Logout</button>
    </form>
</div>
