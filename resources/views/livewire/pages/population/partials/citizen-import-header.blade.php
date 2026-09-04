<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="text-sm font-medium text-slate-500">Kependudukan</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Import Data Warga</h1>
        <p class="mt-1 text-sm text-slate-500">Impor Excel atau CSV dengan validasi sebelum disimpan.</p>
    </div>
    <a href="{{ route($isSuperAdmin ? 'population.admin.citizens.index' : 'population.citizens.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Kembali</a>
</div>
