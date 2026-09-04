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
    <a href="{{ route('letter-types.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
</div>

<div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
    <h2 class="font-semibold text-indigo-900">Kontrol penggunaan</h2>
    <p class="mt-1 text-sm text-indigo-700">Berikan akses berdasarkan kategori organisasi. Semua tenant dalam kategori aktif akan otomatis dapat menggunakan jenis surat ini.</p>
</div>
