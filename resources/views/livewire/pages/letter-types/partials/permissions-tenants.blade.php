<details class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <summary class="cursor-pointer list-none border-b border-slate-100 px-5 py-4 text-sm font-semibold text-slate-900">Override akses tenant individual (legacy)</summary>
    <div class="border-b border-slate-100 p-4">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kode atau nama OPD..." class="form-control w-full sm:max-w-md">
        <p class="mt-2 text-xs text-slate-500">Gunakan hanya untuk pengecualian khusus. Akses utama sebaiknya diberikan melalui kategori.</p>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($tenants as $tenant)
            @php($allowed = in_array($tenant->id, $allowedTenantIds, true))
            <div class="flex items-center justify-between gap-3 p-4">
                <div class="min-w-0"><span class="font-mono text-xs text-slate-400">{{ $tenant->code }}</span><div class="truncate text-sm font-semibold text-slate-900">{{ $tenant->name }}</div></div>
                @if ($allowed)
                    <button type="button" wire:click="confirmRevoke('{{ $tenant->id }}')" class="shrink-0 rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Cabut</button>
                @else
                    <button type="button" wire:click="grant('{{ $tenant->id }}')" class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Beri</button>
                @endif
            </div>
        @empty
            <div class="p-12 text-center text-sm text-slate-500">Tidak ada OPD yang cocok.</div>
        @endforelse
    </div>
    @if ($tenants->hasPages())
        <div class="border-t border-slate-100 p-4">{{ $tenants->onEachSide(1)->links() }}</div>
    @endif
</details>
