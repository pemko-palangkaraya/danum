@props([
    'direction' => 'vertical',
    'gap' => '4',
])

@php
$directions = [
    'vertical' => 'flex-col',
    'horizontal' => 'flex-row',
];
$gaps = [
    '1' => 'gap-1',
    '2' => 'gap-2',
    '3' => 'gap-3',
    '4' => 'gap-4',
    '5' => 'gap-5',
    '6' => 'gap-6',
    '8' => 'gap-8',
];
@endphp

<div {{ $attributes->class(['flex', $directions[$direction] ?? $directions['vertical'], $gaps[$gap] ?? $gaps['4']]) }}>
    {{ $slot }}
</div>
