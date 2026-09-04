<x-ui.card>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        @if($isSuperAdmin)
            <div class="w-full lg:max-w-md">
                <x-ui.field label="Tenant" for="citizens-tenant">
                    <select id="citizens-tenant" wire:model.live="selectedTenantId" class="form-select w-full">
                        <option value="">Pilih tenant...</option>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}">{{ $tenant->name }}{{ $tenant->code ? ' ('.$tenant->code.')' : '' }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
            </div>
        @endif
        <div class="flex w-full flex-col gap-3 sm:flex-row lg:justify-end">
            <input wire:model.live.debounce.300ms="search" placeholder="Cari NIK atau nama..." class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm sm:max-w-sm">
            <select wire:model.live="perPage" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                <option value="10">10 / halaman</option>
                <option value="25">25 / halaman</option>
                <option value="50">50 / halaman</option>
            </select>
        </div>
    </div>
</x-ui.card>
