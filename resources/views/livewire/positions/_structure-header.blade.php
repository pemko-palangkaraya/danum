<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Struktur Organisasi</h1>
        <p class="mt-1 text-sm text-slate-500">Susun hubungan atasan dan bawahan, kepala organisasi, serta pemangku jabatan.</p>
    </div>
    @if($selectedTenantId !== '')
        @if(auth()->user()?->isSuperAdmin())
            <a href="{{ route('positions.structure.pdf', ['tenant' => $selectedTenantId]) }}" target="_blank" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Cetak Struktur PDF</a>
        @else
            <a href="{{ route('positions.structure.pdf.tenant') }}" target="_blank" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Cetak Struktur PDF</a>
        @endif
    @endif
</div>
