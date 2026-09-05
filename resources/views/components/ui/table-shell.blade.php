@props([
    'responsive' => true,
])

<div {{ $attributes->class(['min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    @isset($toolbar)
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">{{ $toolbar }}</div>
    @endisset
    <div @if($responsive) class="overflow-x-auto" @endif>
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="min-w-0 overflow-x-auto border-t border-slate-200 px-4 py-3 sm:px-5">
            {{ $footer }}
        </div>
    @endisset
</div>
