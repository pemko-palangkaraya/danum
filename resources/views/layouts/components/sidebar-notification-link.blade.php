@php
    $compact = $compact ?? false;
    $unreadNotifications = auth()->user()?->unreadNotifications()->count() ?? 0;
@endphp

<a href="{{ route('notifications.index') }}" @class([
    'flex items-center justify-between rounded-xl text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900',
    'w-full px-3 py-2.5' => ! $compact,
    'px-2.5 py-2' => $compact,
])>
    <span class="flex items-center gap-3">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 18H9m9-3H6l1.3-1.7A4.5 4.5 0 0 0 8.2 10.6V9a3.8 3.8 0 1 1 7.6 0v1.6a4.5 4.5 0 0 0 .9 2.7L18 15Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 18a2 2 0 0 0 4 0" />
        </svg>
        @unless ($compact)
            Notifikasi
        @endunless
    </span>
    @if ($unreadNotifications > 0)
        <span class="min-w-5 rounded-full bg-rose-600 px-1.5 py-0.5 text-center text-[11px] font-bold leading-4 text-white">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
    @endif
</a>
