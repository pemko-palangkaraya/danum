@props([
    'title' => null,
    'size' => 'md',
])
@php
$sizes = ['sm' => 'max-w-md', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl', 'xl' => 'max-w-4xl'];
@endphp
<div {{ $attributes->class(['relative w-full rounded-2xl bg-white shadow-xl', $sizes[$size] ?? $sizes['md']]) }}>
    @if($title || isset($header))
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div class="min-w-0">
                @if($title)<h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>@endif
                @isset($header){{ $header }}@endisset
            </div>
            @isset($close){{ $close }}@endisset
        </div>
    @endif
    <div class="px-5 py-5">
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="border-t border-slate-200 px-5 py-4">{{ $footer }}</div>
    @endisset
</div>
