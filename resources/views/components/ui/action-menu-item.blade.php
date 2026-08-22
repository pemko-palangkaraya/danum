@props([
'label',
'variant' => 'default',
])

@php
$classes = match ($variant) {
'danger' => 'text-red-600 hover:bg-red-50',
'success' => 'text-emerald-600 hover:bg-emerald-50',
default => 'text-slate-700 hover:bg-slate-50',
};
@endphp

@if ($attributes->has('href'))

<a
    {{ $attributes->merge([
            'class' => "block w-full px-4 py-2.5 text-left text-sm transition {$classes}",
        ]) }}>
    {{ $label }}
</a>

@else

<button
    type="button"
    {{ $attributes->merge([
            'class' => "block w-full px-4 py-2.5 text-left text-sm transition {$classes}",
        ]) }}>
    {{ $label }}
</button>

@endif