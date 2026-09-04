@props([
    'label' => null,
    'for' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
])

<div {{ $attributes->class(['space-y-1.5']) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="text-sm font-medium text-slate-700">
            {{ $label }}
            @if($required)<span class="text-red-600">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if($hint && !$error)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if($error)
        <p class="text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
