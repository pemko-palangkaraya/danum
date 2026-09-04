<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="text-sm text-slate-500">Master Jenis Surat</p>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $letterType->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $letterType->code }} · Riwayat versi template dan snapshot variabel input.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('letter-types.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
        <button wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">+ Tambah Versi</button>
    </div>
</div>

<div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
    <strong>Aturan versi:</strong> versi yang sudah dibuat tidak diedit atau dihapus. Template dan daftar variabel disimpan sebagai snapshot. Surat lama tetap menggunakan versi yang tercatat saat surat dibuat.
</div>
