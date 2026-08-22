<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-semibold tracking-tight text-slate-900">Letter Type Management</h1><p class="mt-1 text-sm text-slate-500">Master jenis surat global. Super Admin menentukan variabel dan template DOCX.</p></div>
        <button wire:click="create" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Add Letter Type</button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <input wire:model.live="search" type="search" placeholder="Cari kode atau nama..." class="w-full rounded-xl border-slate-200 text-sm sm:max-w-sm">
            <select wire:model.live="filter" class="rounded-xl border-slate-200 text-sm sm:w-44"><option value="active">Active</option><option value="draft">Draft</option><option value="validated">Validated</option><option value="retired">Retired</option><option value="deleted">Deleted</option></select>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($letterTypes as $letterType)
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0"><div class="flex items-center gap-2"><span class="font-mono text-xs font-semibold text-slate-400">{{ $letterType->code }}</span><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $letterType->status->value }}</span></div><h2 class="mt-1 font-semibold text-slate-900">{{ $letterType->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $letterType->description ?: 'Tidak ada deskripsi.' }}</p><p class="mt-2 text-xs text-slate-400">{{ count($letterType->variables ?? []) }} variabel · Template {{ $letterType->template_path ? 'DOCX' : 'belum diupload' }}</p></div>
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
                <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold">{{ $editingId ? 'Edit Letter Type' : 'Add Letter Type' }}</h2><p class="mt-1 text-sm text-slate-500">Tentukan variabel terlebih dahulu, lalu upload DOCX. Sistem akan melakukan pencocokan 1:1 sebelum menyimpan.</p></div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label class="text-sm font-medium">Code</label><input wire:model="code" class="mt-1 w-full rounded-xl border-slate-200">@error('code')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="text-sm font-medium">Name</label><input wire:model="name" class="mt-1 w-full rounded-xl border-slate-200">@error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label class="text-sm font-medium">Description</label><textarea wire:model="description" rows="2" class="mt-1 w-full rounded-xl border-slate-200"></textarea></div>

                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div><h3 class="text-sm font-semibold text-indigo-900">Variabel Template</h3><p class="mt-1 text-xs text-indigo-700">Masukkan nama variabel yang memang akan digunakan di DOCX. Satu variabel per baris. Tidak perlu menulis <code>&#123;&#123;</code> dan <code>&#125;&#125;</code>.</p></div>
                        </div>
                        <textarea wire:model="variables_input" rows="7" placeholder="number&#10;recipient_name&#10;recipient_address&#10;subject&#10;tenant_name&#10;tenant_city&#10;tenant_head_name" class="mt-3 w-full rounded-xl border-indigo-200 bg-white font-mono text-sm"></textarea>
                        @error('variables_input')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                        <div class="mt-3 rounded-lg bg-white/80 p-3 text-xs text-indigo-800"><strong>Contoh:</strong> <code>recipient_name</code> akan dicocokkan dengan <code>&#123;&#123;recipient_name&#125;&#125;</code> di DOCX.</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-4"><div><label class="text-sm font-semibold">Template DOCX</label><p class="mt-1 text-xs text-slate-500">Maksimal 10 MB. Semua placeholder <code>&#123;&#123;variable&#125;&#125;</code> harus sama persis dengan daftar variabel di atas.</p></div><input wire:model="template_file" type="file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="block max-w-xs text-sm"></div>
                        @error('template_file')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                        <div class="mt-3 rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-600">Saat disimpan, DANUM melakukan <strong>cross-check</strong>: variabel yang didaftarkan tetapi tidak ada di DOCX akan ditolak, begitu juga variabel yang muncul di DOCX tetapi belum didaftarkan.</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4"><h3 class="text-sm font-semibold text-slate-900">Variabel Sistem yang umum digunakan</h3><div class="mt-3 grid gap-2 sm:grid-cols-2"><div class="rounded-lg bg-slate-50 px-3 py-2"><code class="text-xs font-semibold text-indigo-700">&#123;&#123;number&#125;&#125;</code><p class="text-xs text-slate-500">Nomor surat</p></div><div class="rounded-lg bg-slate-50 px-3 py-2"><code class="text-xs font-semibold text-indigo-700">&#123;&#123;tenant_name&#125;&#125;</code><p class="text-xs text-slate-500">Nama tenant</p></div><div class="rounded-lg bg-slate-50 px-3 py-2"><code class="text-xs font-semibold text-indigo-700">&#123;&#123;tenant_city&#125;&#125;</code><p class="text-xs text-slate-500">Kota tenant</p></div><div class="rounded-lg bg-slate-50 px-3 py-2"><code class="text-xs font-semibold text-indigo-700">&#123;&#123;tenant_head_name&#125;&#125;</code><p class="text-xs text-slate-500">Nama kepala/pejabat</p></div></div><p class="mt-3 text-xs text-slate-500">Tetap daftarkan variabel sistem yang ingin dipakai oleh template. Daftar ini hanya contoh, bukan whitelist.</p></div>

                    <div><label class="text-sm font-medium">Status</label><select wire:model="status" class="mt-1 w-full rounded-xl border-slate-200"><option value="draft">Draft</option><option value="validated">Validated</option><option value="active">Active</option><option value="retired">Retired</option></select></div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">Cancel</button><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Save Master Template</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
