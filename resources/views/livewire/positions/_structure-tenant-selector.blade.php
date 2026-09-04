<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <label class="text-sm font-semibold text-slate-700">Pilih organisasi</label>
    <select wire:model.live="selectedTenantId" class="form-select mt-2 w-full sm:max-w-md">
        <option value="">Pilih organisasi...</option>
        @foreach($tenants as $tenant)
            <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
        @endforeach
    </select>
</div>
