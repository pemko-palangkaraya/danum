<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Letter Type Management</h1>
            <p class="mt-1 text-sm text-slate-500">Master jenis surat global. Setiap jenis surat memakai satu klasifikasi untuk menentukan penomoran.</p>
        </div>
        <button wire:click="create" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Add Letter Type</button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kode atau nama..." class="form-control sm:max-w-sm">
            <select wire:model.live="filter" class="form-select sm:w-52">
                <option value="active">Active</option><option value="draft">Draft</option><option value="validated">Validated</option><option value="retired">Retired</option><option value="deleted">Deleted</option>
            </select>
        </div>
        @if ($filter === 'deleted')
            <div class="border-b border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">Menampilkan jenis surat yang sudah dihapus atau sedang menunggu penghapusan pada akhir hari.</div>
        @endif
        <div class="divide-y divide-slate-100">
            @forelse ($letterTypes as $letterType)
                <div wire:key="letter-type-{{ $letterType->id }}" class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs font-semibold text-slate-400">{{ $letterType->code }}</span>
                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ $letterType->classification?->code ?? '-' }}</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $letterType->status->value }}</span>
                            <span class="rounded-full bg-cyan-50 px-2 py-0.5 text-xs font-medium text-cyan-700">{{ ($letterType->font_family ?? \App\Enums\LetterFont::ARIAL)->value }}</span>
                            @if ($letterType->deletionIsScheduled())<span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">dihapus 23:59:59</span>@elseif ($letterType->trashed())<span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">deleted</span>@endif
                        </div>
                        <h2 class="mt-1 font-semibold text-slate-900">{{ $letterType->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $letterType->description ?: 'Tidak ada deskripsi.' }}</p>
                        <p class="mt-2 text-xs text-slate-400">Klasifikasi: {{ $letterType->classification?->name ?: 'Belum diatur' }} · {{ count($letterType->variables ?? []) }} variabel · Template {{ $letterType->template_path ? 'DOCX tersedia' : 'belum tersedia' }} · Font: {{ ($letterType->font_family ?? \App\Enums\LetterFont::ARIAL)->value }} 12 pt · Masa berlaku: @switch($letterType->validity_period ?? 'none') @case('1_week') 1 minggu @break @case('2_weeks') 2 minggu @break @case('1_month') 1 bulan @break @case('3_months') 3 bulan @break @case('6_months') 6 bulan @break @case('1_year') 1 tahun @break @default Tidak ada @endswitch</p>
                        @if ($letterType->deletionIsScheduled())<p class="mt-2 text-xs font-medium text-amber-700">Masih dapat digunakan sampai akhir hari ini. Penghapusan dijalankan setelah pukul 23:59:59.</p>@endif
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if (! $letterType->trashed() && ! $letterType->deletionIsScheduled())
                            @if ($letterType->isGlobal())<a href="{{ route('letter-types.permissions', $letterType) }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">Atur Akses OPD</a><a href="{{ route('letter-types.versions', $letterType) }}" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-medium text-violet-700 hover:bg-violet-100">Kelola Versi</a>@endif
                            <button wire:click="edit('{{ $letterType->id }}')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit Master</button>
                            <button wire:click="openDelete('{{ $letterType->id }}')" class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Delete</button>
                        @elseif ($letterType->deletionIsScheduled())<span class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700">Menunggu penghapusan</span>@elseif ($letterType->trashed())<button wire:click="openRestore('{{ $letterType->id }}')" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">Restore</button>@else<span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">Tidak ada tindakan</span>@endif
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-sm text-slate-500">{{ $filter === 'deleted' ? 'Belum ada jenis surat yang dihapus.' : 'Belum ada jenis surat.' }}</div>
            @endforelse
        </div>
        <x-ui.table-footer :paginator="$letterTypes" label="letter types" />
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Master Jenis Surat' : 'Add Letter Type' }}</h2><p class="mt-1 text-sm text-slate-500">Klasifikasi menjadi dasar penomoran surat. Font adalah bagian dari standar TND dan tidak dipilih saat membuat surat.</p></div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label class="text-sm font-medium text-slate-700">Klasifikasi Surat</label><select wire:model="letter_classification_id" class="form-select mt-1"><option value="">Pilih klasifikasi...</option>@foreach ($classifications as $classification)<option value="{{ $classification->id }}">{{ $classification->code }} — {{ $classification->name }}</option>@endforeach</select>@error('letter_classification_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="text-sm font-medium text-slate-700">Code</label><input wire:model="code" class="form-control mt-1">@error('code')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label class="text-sm font-medium text-slate-700">Name</label><input wire:model="name" class="form-control mt-1">@error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-medium text-slate-700">Description</label><textarea wire:model="description" rows="2" class="form-textarea mt-1"></textarea></div>
                    <div class="rounded-xl border border-cyan-100 bg-cyan-50 p-4"><label class="text-sm font-semibold text-cyan-900">Font TND</label><p class="mt-1 text-xs text-cyan-700">DANUM hanya mengizinkan Arial atau Bookman Old Style. Sistem akan memaksa font ini ke seluruh teks DOCX hasil.</p><select wire:model="font_family" class="form-select mt-3 bg-white">@foreach ($fonts as $font)<option value="{{ $font->value }}">{{ $font->value }} — 12 pt</option>@endforeach</select>@error('font_family')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4"><label class="text-sm font-semibold text-emerald-900">Masa Berlaku Surat</label><p class="mt-1 text-xs text-emerald-700">Pilih masa berlaku yang sudah ditentukan. Perhitungan dilakukan dari tanggal surat diterbitkan.</p><select wire:model="validity_period" class="form-select mt-3 bg-white"><option value="none">Tidak memiliki masa berlaku</option><option value="1_week">1 minggu</option><option value="2_weeks">2 minggu</option><option value="1_month">1 bulan</option><option value="3_months">3 bulan</option><option value="6_months">6 bulan</option><option value="1_year">1 tahun</option></select>@error('validity_period')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4"><h3 class="text-sm font-semibold text-indigo-900">Variabel Template</h3><p class="mt-1 text-xs text-indigo-700">Satu variabel biasa per baris. <code>number</code> diisi otomatis oleh sistem berdasarkan klasifikasi dan tenant.</p><textarea wire:model="variables_input" rows="8" placeholder="number&#10;recipient_name&#10;recipient_nik&#10;recipient_address&#10;subject&#10;date&#10;&#10;@repeat pelaksana|Nama:nama,NIP:nip,Jabatan:jabatan" class="form-textarea mt-3 font-mono text-sm"></textarea>@error('variables_input')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    @if ($editingId)<div class="rounded-xl border border-violet-100 bg-violet-50 p-4"><p class="text-sm font-semibold text-violet-900">Template DOCX dikelola melalui Kelola Versi</p><p class="mt-1 text-xs text-violet-700">Untuk mengganti format dokumen, pilih <strong>Kelola Versi</strong>. Versi baru akan memiliki periode berlaku dan catatan perubahan sendiri.</p></div>@endif
                    <div><label class="text-sm font-medium text-slate-700">Status</label><select wire:model="status" class="form-select mt-1"><option value="draft">Draft</option><option value="validated">Validated</option><option value="active">Active</option><option value="retired">Retired</option></select></div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">{{ $editingId ? 'Save Master' : 'Create Letter Type' }}</button></div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDeleteConfirm)<div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/50 p-4" wire:click.self="closeDeleteConfirm"><div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"><div class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold text-slate-900">Hapus Jenis Surat?</h2><p class="mt-1 text-sm text-slate-500">Jenis surat tidak akan langsung dihapus saat tombol konfirmasi ditekan.</p></div><div class="px-6 py-5"><p class="text-sm text-slate-700">Kamu akan menjadwalkan penghapusan:</p><p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-900">{{ $deleteName }}</p><div class="mt-3 rounded-lg border border-amber-100 bg-amber-50 px-3 py-3 text-xs text-amber-800">Jenis surat tetap dapat digunakan oleh tenant sampai akhir hari ini dan akan dipindahkan ke data terhapus setelah <strong>23:59:59</strong>.</div></div><div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4"><button type="button" wire:click="closeDeleteConfirm" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button type="button" wire:click="confirmDelete" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white">Jadwalkan Hapus</button></div></div></div>@endif
    @if ($showRestoreConfirm)<div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/50 p-4" wire:click.self="closeRestoreConfirm"><div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"><div class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold text-slate-900">Pulihkan Jenis Surat?</h2><p class="mt-1 text-sm text-slate-500">Jenis surat akan tersedia kembali untuk tenant yang memiliki akses.</p></div><div class="px-6 py-5"><p class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-900">{{ $restoreName }}</p></div><div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4"><button type="button" wire:click="closeRestoreConfirm" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button type="button" wire:click="confirmRestore" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white">Pulihkan</button></div></div></div>@endif
</div>
