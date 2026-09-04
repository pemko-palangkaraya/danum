@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'id' => null,
])

<x-ui.field :label="$label" :error="$error" :hint="$hint" :required="$required" :for="$id">
    <select
        @if($id) id="{{ $id }}" @endif
        {{ $attributes->merge(['class' => 'form-select w-full']) }}>
        {{ $slot }}
    </select>
</x-ui.field>
