<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Jabatan</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola master jabatan, pejabat, dan kredensial TTE.</p>
    </div>

    @if($isSuperAdmin)
        <button type="button" wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
            + Tambah Jabatan
        </button>
    @endif
</div>

@if($isSuperAdmin)
    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        Jabatan adalah master organisasi. Buat jabatan sekali di sini; pergantian pejabat dicatat melalui riwayat pemegang jabatan.
    </div>
@endif
