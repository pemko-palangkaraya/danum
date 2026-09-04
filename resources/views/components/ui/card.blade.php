@props([
    'class' => '',
    'padding' => 'p-5',
])

<div {{ $attributes->class(["overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm {$padding}"]) }}>
    {{ $slot }}
</div>
