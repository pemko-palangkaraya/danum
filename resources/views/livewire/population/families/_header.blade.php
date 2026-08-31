<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="text-sm font-medium text-slate-500">Kependudukan</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Kartu Keluarga</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola data kartu keluarga dan anggota keluarga.</p>
    </div>
    @if(auth()->user()->hasPermission('population.manage') && (!auth()->user()->isSuperAdmin() || $selectedTenantId))
        <button wire:click="create" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
            <span class="text-base leading-none">+</span> Tambah KK
        </button>
    @endif
</div>
