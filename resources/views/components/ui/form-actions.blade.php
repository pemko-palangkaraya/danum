@props(['align' => 'end'])
@php
$alignments = ['start' => 'justify-start', 'center' => 'justify-center', 'end' => 'justify-end', 'between' => 'justify-between'];
@endphp
<div {{ $attributes->class(['flex flex-col gap-2 pt-2 sm:flex-row', $alignments[$align] ?? $alignments['end']]) }}>
    {{ $slot }}
</div>
