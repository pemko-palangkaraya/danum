<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="text-sm font-medium text-slate-500">Kependudukan</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Data Warga</h1>
        <p class="mt-1 text-sm text-slate-500">Master data penduduk yang terdaftar dalam tenant.</p>
    </div>

    <div class="flex flex-wrap gap-2">
        @if($tenantSelected)
            @if($canView)
                <a href="{{ route('population.citizens.export', ['format' => 'xlsx', 'tenant_id' => $selectedTenantId ?? null]) }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Export Excel</a>
                <a href="{{ route('population.citizens.export', ['format' => 'csv', 'tenant_id' => $selectedTenantId ?? null]) }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Export CSV</a>
            @endif
            @if($canManage)
                <a href="{{ route('population.citizens.import') }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Import</a>
                <button wire:click="create" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"><span>+</span> Tambah Warga</button>
            @endif
        @endif
    </div>
</div>
