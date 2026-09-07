<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Akses OPD individual</h2>
                <p class="mt-1 text-xs text-slate-500">Berikan atau cabut akses jenis surat untuk OPD tertentu.</p>
            </div>
            <span class="w-fit rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">5 per halaman</span>
        </div>
    </div>

    <div class="border-b border-slate-100 bg-slate-50/70 p-4">
        <label for="letter-type-permission-search" class="sr-only">Cari OPD</label>
        <div class="relative w-full sm:max-w-md">
            <input
                id="letter-type-permission-search"
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Cari kode atau nama OPD..."
                class="form-control w-full pr-10">
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">⌕</span>
        </div>
        <p class="mt-2 text-xs text-slate-500">Pencarian berdasarkan kode atau nama OPD. Hasil akan diperbarui otomatis.</p>
    </div>

    <div class="divide-y divide-slate-100">
        @forelse ($tenants as $tenant)
            @php($allowed = in_array($tenant->id, $allowedTenantIds, true))
            <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <span class="font-mono text-xs font-semibold text-slate-400">{{ $tenant->code }}</span>
                    <div class="truncate text-sm font-semibold text-slate-900">{{ $tenant->name }}</div>
                </div>
                @if ($allowed)
                    <button type="button" wire:click="confirmRevoke('{{ $tenant->id }}')" class="shrink-0 rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Cabut Akses</button>
                @else
                    <button type="button" wire:click="grant('{{ $tenant->id }}')" class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Beri Akses</button>
                @endif
            </div>
        @empty
            <div class="p-12 text-center text-sm text-slate-500">Tidak ada OPD yang cocok.</div>
        @endforelse
    </div>

    <x-ui.table-footer :paginator="$tenants" label="OPD" />
</div>
