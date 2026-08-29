<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-2 text-sm text-slate-500">
                <a href="{{ route('letter-types.index') }}" class="hover:text-slate-900">Letter Types</a>
                <span class="mx-1">/</span>
                Permissions
            </div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Atur Akses OPD</h1>
            <p class="mt-1 text-sm text-slate-500">
                <span class="font-semibold text-slate-700">{{ $letterType->code }}</span> — {{ $letterType->name }}
            </p>
        </div>
        <a href="{{ route('letter-types.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Kembali
        </a>
    </div>

    <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
        <h2 class="font-semibold text-indigo-900">Kontrol penggunaan</h2>
        <p class="mt-1 text-sm text-indigo-700">
            Berikan akses berdasarkan kategori organisasi. Semua tenant dalam kategori aktif akan otomatis dapat menggunakan jenis surat ini.
        </p>
    </div>

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
            <div class="border-t border-slate-100 p-4">
                {{ $tenants->onEachSide(1)->links() }}
            </div>
        @endif
    </details>

    <x-ui.confirmation-modal
        modal-id="letter-type-permission-revoke"
        title="Cabut Akses OPD"
        :message="'Apakah Anda yakin ingin mencabut akses '.$selectedTenantName.' untuk jenis surat ini?'"
        confirm-text="Cabut Akses"
        cancel-text="Batal"
        confirm-action="revoke"
        cancel-action="cancelRevoke"
        variant="danger" />

    <x-ui.confirmation-modal
        modal-id="letter-type-category-permission-revoke"
        title="Cabut Akses Kategori"
        :message="'Apakah Anda yakin ingin mencabut akses '.$selectedCategoryName.' untuk jenis surat ini?'"
        confirm-text="Cabut Akses"
        cancel-text="Batal"
        confirm-action="revokeCategory"
        cancel-action="cancelRevokeCategory"
        variant="danger" />
</div>
