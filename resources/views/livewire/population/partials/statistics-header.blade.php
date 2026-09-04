<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">⌁</span>
            <h1 class="text-xl font-bold tracking-tight text-gray-900">Statistik Kependudukan</h1>
        </div>
        <p class="mt-1 text-sm text-gray-500">Ringkasan demografi dan komposisi penduduk.</p>
    </div>
    @if($isSuperAdmin)
        <select wire:model.live="selectedTenantId" class="rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Pilih tenant</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
            @endforeach
        </select>
    @endif
</div>
