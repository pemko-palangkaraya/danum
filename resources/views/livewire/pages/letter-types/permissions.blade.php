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
            Jenis surat ini bersifat global. Hanya OPD yang diberi akses di bawah yang dapat menggunakannya pada proses pembuatan surat.
        </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-4">
            <input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="Cari kode atau nama OPD..."
                class="form-control w-full sm:max-w-md">
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($tenants as $tenant)
                @php($allowed = in_array($tenant->id, $allowedTenantIds, true))
                <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-semibold text-slate-400">{{ $tenant->code }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $allowed ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $allowed ? 'Diizinkan' : 'Tidak diizinkan' }}
                            </span>
                        </div>
                        <h2 class="mt-1 font-semibold text-slate-900">{{ $tenant->name }}</h2>
                    </div>

                    @if ($allowed)
                        <button
                            type="button"
                            wire:click="revoke('{{ $tenant->id }}')"
                            wire:confirm="Cabut akses {{ $tenant->name }} untuk jenis surat ini?"
                            class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">
                            Cabut Akses
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="grant('{{ $tenant->id }}')"
                            class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Beri Akses
                        </button>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center text-sm text-slate-500">Tidak ada OPD yang cocok.</div>
            @endforelse
        </div>
    </div>
</div>
