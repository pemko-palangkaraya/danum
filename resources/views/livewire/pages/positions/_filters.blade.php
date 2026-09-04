<div class="flex flex-col gap-3 sm:flex-row">
    @if($isSuperAdmin)
        <select wire:model.live="selectedTenantId" class="form-select sm:w-72">
            <option value="">Pilih organisasi...</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
            @endforeach
        </select>
    @endif

    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kode atau nama jabatan..." class="form-control sm:max-w-sm">

    <select wire:model.live="filter" class="form-select sm:w-44">
        <option value="active">Aktif</option>
        <option value="inactive">Tidak Aktif</option>
        <option value="all">Semua</option>
        @if($isSuperAdmin)
            <option value="deleted">Dihapus</option>
        @endif
    </select>
</div>
