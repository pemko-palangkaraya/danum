@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'id' => null,
])

<x-ui.field :label="$label" :error="$error" :hint="$hint" :required="$required" :for="$id">
    <input
        @if($id) id="{{ $id }}" @endif
        @if($required) required @endif
        @if($error) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => 'form-control w-full']) }}>
</x-ui.field>
