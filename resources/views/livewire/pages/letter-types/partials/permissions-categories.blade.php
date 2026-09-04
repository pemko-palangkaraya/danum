<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="text-sm font-semibold text-slate-900">Akses berdasarkan kategori</h2>
        <p class="mt-1 text-xs text-slate-500">Tidak perlu memilih OPD satu per satu. Pilih kategori seperti Kelurahan, Kecamatan, atau Dinas.</p>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($categories as $category)
            @php($allowed = in_array($category->id, $allowedCategoryIds, true))
            <div class="flex items-center justify-between gap-4 p-5">
                <div class="min-w-0">
                    <h2 class="truncate font-semibold text-slate-900">{{ $category->name }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $category->tenants()->where('status', true)->count() }} tenant aktif</p>
                </div>
                @if ($allowed)
                    <button type="button" wire:click="confirmRevokeCategory({{ $category->id }})" class="shrink-0 rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Cabut Akses</button>
                @else
                    <button type="button" wire:click="grantCategory({{ $category->id }})" class="shrink-0 rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Beri Akses</button>
                @endif
            </div>
        @empty
            <div class="p-12 text-center text-sm text-slate-500">Belum ada kategori aktif.</div>
        @endforelse
    </div>
</div>
