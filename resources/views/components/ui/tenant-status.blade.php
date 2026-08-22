@props([
'status',
])

@php
$value = $status?->value ?? $status ?? null;
$value = (string) $value;

$label = match ($value) {
'1' => 'Active',
'0' => 'Inactive',
default => 'Unknown',
};

$classes = match ($value) {
'1' => 'bg-emerald-50 text-emerald-700',
'0' => 'bg-slate-100 text-slate-600',
default => 'bg-amber-50 text-amber-700',
};
@endphp

<span
    @class([ 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold' ,
    $classes,
    ])>
    {{ $label }}
</span>