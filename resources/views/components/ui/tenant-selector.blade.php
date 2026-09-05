@props([
    'tenants' => collect(),
    'model' => 'selectedTenantId',
    'id' => 'tenant-selector',
    'placeholder' => 'Pilih tenant...',
])

<label for="{{ $id }}" class="sr-only">Tenant</label>
<select
    id="{{ $id }}"
    wire:model.live="{{ $model }}"
    {{ $attributes->class([
        'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700',
        'outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100',
    ]) }}>
    <option value="">{{ $placeholder }}</option>
    @foreach($tenants as $tenant)
        <option value="{{ $tenant->id }}">
            {{ $tenant->name }}{{ $tenant->code ? ' ('.$tenant->code.')' : '' }}
        </option>
    @endforeach
</select>
