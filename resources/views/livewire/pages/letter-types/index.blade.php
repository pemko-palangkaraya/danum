<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Letter Type Management</h1>
            <p class="mt-1 text-sm text-slate-500">Master jenis surat global. Super Admin menentukan masa berlaku, variabel, dan template DOCX.</p>
        </div>
        <button wire:click="create" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Add Letter Type</button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <input wire:model.live="search" type="search" placeholder="Cari kode atau nama..." class="form-control sm:max-w-sm">
            <select wire:model.live="filter" class="form-select sm:w-44"><option value="active">Active</option><option value="draft">Draft</option><option value="validated">Validated</option><option value="retired">Retired</option></select>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($letterTypes as $letterType)
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2"><span class="font-mono text-xs font-semibold text-slate-400">{{ $letterType->code }}</span><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $letterType->status->value }}</span></div>
                        <h2 class="mt-1 font-semibold text-slate-900">{{ $letterType->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $letterType->description ?: 'Tidak ada deskripsi.' }}</p>
                        <p class="mt-2 text-xs text-slate-400">{{ count($letterType->variables ?? []) }} variabel · Template {{ $letterType->template_path ? 'DOCX' : 'belum diupload' }} · Masa berlaku: {{ match($letterType->validity_period ?? 'none') { '1_week' => '1 minggu', '2_weeks' => '2 minggu', '1_month' => '1 bulan', '3_months' => '3 bulan', '6_months' => '6 bulan', '1_year' => '1 tahun', default => 'Tidak ada' } }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2"><button wire:click="edit('{{ $letterType->id }}')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit</button><button wire:click="delete('{{ $letterType->id }}')" wire:confirm="Hapus jenis surat ini?" class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Delete</button></div>
                </div>
            @empty
                <div class="p-12 text-center text-sm text-slate-500">Belum ada jenis surat.</div>
            @endforelse
        </div>
        <div class="border-t border-slate-100 p-4">{{ $letterTypes->links() }}</div>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Letter Type' : 'Add Letter Type' }}</h2><p class="mt-1 text-sm text-slate-500">Tentukan masa berlaku dan variabel, lalu upload DOCX. Sistem mencocokkan placeholder DOCX dengan daftar variabel sebelum menyimpan.</p></div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label class="text-sm font-medium text-slate-700">Code</label><input wire:model="code" class="form-control mt-1">@error('code')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="text-sm font-medium text-slate-700">Name</label><input wire:model="name" class="form-control mt-1">@error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label class="text-sm font-medium text-slate-700">Description</label><textarea wire:model="description" rows="2" class="form-textarea mt-1"></textarea></div>

                    <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                        <label class="text-sm font-semibold text-emerald-900">Masa Berlaku Surat</label>
                        <p class="mt-1 text-xs text-emerald-700">Pilih masa berlaku yang sudah ditentukan. Perhitungan dilakukan dari tanggal surat diterbitkan dan menggunakan kalender, bukan input jumlah hari manual.</p>
                        <select wire:model="validity_period" class="form-select mt-3 bg-white">
                            <option value="none">Tidak memiliki masa berlaku</option>
                            <option value="1_week">1 minggu</option>
                            <option value="2_weeks">2 minggu</option>
                            <option value="1_month">1 bulan</option>
                            <option value="3_months">3 bulan</option>
                            <option value="6_months">6 bulan</option>
                            <option value="1_year">1 tahun</option>
                        </select>
                        @error('validity_period')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4"><div><h3 class="text-sm font-semibold text-indigo-900">Variabel Template</h3><p class="mt-1 text-xs text-indigo-700">Masukkan nama variabel yang benar-benar dipakai di DOCX. Satu variabel per baris. Tidak perlu menulis &#123;&#123; dan &#125;&#125;.</p></div><textarea wire:model="variables_input" rows="7" placeholder="number&#10;recipient_name&#10;recipient_nik&#10;recipient_address&#10;subject&#10;tenant_name&#10;tenant_city&#10;tenant_head_name&#10;date" class="form-textarea mt-3 font-mono text-sm"></textarea>@error('variables_input')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror<div class="mt-3 rounded-lg bg-white p-3 text-xs text-indigo-800"><strong>Contoh:</strong> <code>recipient_name</code> akan dicocokkan dengan <code>&#123;&#123;recipient_name&#125;&#125;</code> di DOCX.</div></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><label class="text-sm font-semibold text-slate-800">Template DOCX</label><p class="mt-1 text-xs text-slate-500">Maksimal 10 MB. Placeholder DOCX harus sama persis dengan daftar variabel.</p></div><input wire:model="template_file" type="file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="block w-full max-w-xs rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700"></div>@error('template_file')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror<div class="mt-3 rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-600">Saat disimpan, DANUM melakukan <strong>cross-check</strong>. Variabel yang hilang atau berlebih akan ditolak.</div></div>
                    <div><label class="text-sm font-medium text-slate-700">Status</label><select wire:model="status" class="form-select mt-1"><option value="draft">Draft</option><option value="validated">Validated</option><option value="active">Active</option><option value="retired">Retired</option></select></div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save Master Template</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
