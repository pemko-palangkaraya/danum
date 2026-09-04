@props([
    'padding' => 'p-5',
])

<div {{ $attributes->class(['overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    @isset($header)
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            {{ $header }}
        </div>
    @endisset

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
            {{ $footer }}
        </div>
    @endisset
</div>
