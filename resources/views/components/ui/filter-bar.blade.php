@props(['align' => 'end'])
<div {{ $attributes->class(['flex flex-col gap-3 lg:flex-row lg:items-end', $align === 'between' ? 'lg:justify-between' : 'lg:justify-end']) }}>
    {{ $slot }}
</div>
