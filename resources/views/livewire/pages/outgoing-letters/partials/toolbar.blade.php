<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Surat Keluar</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $isSuperAdmin ? 'Arsip seluruh surat keluar tenant dan pemulihan surat yang dihapus.' : 'Buat, periksa, kirim untuk verifikasi, terbitkan, dan verifikasi publik surat keluar.' }}</p>
    </div>
    @unless($isSuperAdmin)
        @can('create', \App\Models\OutgoingLetter::class)
            <button wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Buat Surat</button>
        @endcan
    @endunless
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
        <input wire:model.live="search" type="search" placeholder="Cari nomor, penerima, perihal..." class="form-control sm:max-w-md">
        <select wire:model.live="filter" class="form-select sm:w-52">
            <option value="all">{{ $isSuperAdmin ? 'Surat Aktif' : 'Semua' }}</option>
            <option value="draft">Draft</option>
            <option value="validated">Tervalidasi</option>
            <option value="issued">Diterbitkan</option>
            <option value="withdrawn">Ditarik</option>
            <option value="cancelled">Dibatalkan</option>
            @if($isSuperAdmin)<option value="deleted">Dihapus</option>@endif
        </select>
    </div>
</div>