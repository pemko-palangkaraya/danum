<div class="border-t border-slate-100 p-4">
    <div class="mb-3 rounded-xl bg-slate-50 px-3 py-2.5 text-xs text-slate-500">
        <div class="font-semibold text-slate-700">Waktu Server</div>
        <div class="mt-0.5 font-mono text-sm text-slate-900" data-server-clock data-server-timestamp="{{ now()->getTimestampMs() }}" data-server-timezone="{{ config('app.timezone') }}">{{ now()->format('d M Y, H:i:s') }}</div>
        <div class="mt-0.5 text-[11px] text-slate-400">{{ config('app.timezone') }}</div>
    </div>

    <div class="mb-2">
        @include('layouts.components.sidebar-notification-link')
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Logout</button>
    </form>
</div>
