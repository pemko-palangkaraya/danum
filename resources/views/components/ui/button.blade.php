@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'loading' => null,
])

@php
$variants = [
    'primary' => 'bg-slate-900 text-white hover:bg-slate-800 focus:ring-slate-300',
    'secondary' => 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-200',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-300',
    'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-300',
    'ghost' => 'text-slate-600 hover:bg-slate-100 focus:ring-slate-200',
];
$sizes = [
    'sm' => 'px-3 py-2 text-xs',
    'md' => 'px-4 py-2.5 text-sm',
    'lg' => 'px-5 py-3 text-sm',
];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-xl font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 {$variants[$variant]} {$sizes[$size]}"]) }}
    @if($loading) wire:loading.attr="disabled" wire:target="{{ $loading }}" @endif
>
    @if($loading)
        <span wire:loading wire:target="{{ $loading }}">Memproses...</span>
        <span wire:loading.remove wire:target="{{ $loading }}">{{ $slot }}</span>
    @else
        {{ $slot }}
    @endif
</button>
