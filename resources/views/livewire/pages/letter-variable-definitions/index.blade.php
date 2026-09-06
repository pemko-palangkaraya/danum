<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Master Surat</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Variabel Surat</h1>
            <p class="mt-1 text-sm text-slate-500">Atur tipe input, sumber data, dan perilaku variabel template tanpa mengubah kode aplikasi.</p>
        </div>
        <button wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Tambah Variabel</button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari key atau label..." class="form-control sm:max-w-md">
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Key</th><th class="px-4 py-3">Label</th><th class="px-4 py-3">Tipe</th><th class="px-4 py-3">Sumber</th><th class="px-4 py-3">Perilaku</th><th class="px-4 py-3 text-right">Aksi</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($definitions as $definition)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-mono text-sm text-slate-700">{{ $definition->key }}</td>
                            <td class="px-4 py-3"><div class="font-medium text-slate-900">{{ $definition->label }}</div>@if($definition->description)<div class="mt-0.5 text-xs text-slate-500">{{ $definition->description }}</div>@endif</td>
                            <td class="px-4 py-3"><span class="rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $definition->type }}</span></td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $definition->source }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $definition->required ? 'Wajib' : 'Opsional' }} · {{ $definition->readonly ? 'Readonly' : 'Bisa diisi' }} · {{ $definition->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="px-4 py-3 text-right"><button wire:click="edit('{{ $definition->id }}')" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada definisi variabel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-ui.table-footer :paginator="$definitions" label="variabel" />
    </div>

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" wire:click.self="$set('showForm', false)">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Variabel' : 'Tambah Variabel' }}</h2><p class="mt-1 text-sm text-slate-500">Key ini digunakan langsung pada template DOCX sebagai <code class="rounded bg-slate-100 px-1">@{{key}}</code>.</p></div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="text-sm font-medium text-slate-700">Key</label><input wire:model="key" class="form-control mt-1 font-mono" placeholder="nama_saksi">@error('key')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="text-sm font-medium text-slate-700">Label</label><input wire:model="label" class="form-control mt-1" placeholder="Nama Saksi">@error('label')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="text-sm font-medium text-slate-700">Tipe Input</label><select wire:model.live="type" class="form-select mt-1">@foreach($types as $item)<option value="{{ $item }}">{{ ucfirst($item) }}</option>@endforeach</select></div>
                        <div><label class="text-sm font-medium text-slate-700">Sumber Data</label><select wire:model="source" class="form-select mt-1">@foreach($sources as $item)<option value="{{ $item }}">{{ ucfirst($item) }}</option>@endforeach</select></div>
                    </div>
                    @if($type === 'select')
                        <div><label class="text-sm font-medium text-slate-700">Pilihan Select</label><textarea wire:model="options_input" rows="5" class="form-textarea mt-1 font-mono" placeholder="aktif|Aktif&#10;tidak_aktif|Tidak Aktif"></textarea><p class="mt-1 text-xs text-slate-500">Satu pilihan per baris: <code>value|Label</code>.</p>@error('options_input')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    @endif
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="required" class="rounded"> Wajib</label>
                        <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="readonly" class="rounded"> Readonly</label>
                        <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model="is_active" class="rounded"> Aktif</label>
                    </div>
                    <div><label class="text-sm font-medium text-slate-700">Keterangan</label><textarea wire:model="description" rows="2" class="form-textarea mt-1" placeholder="Penjelasan penggunaan variabel"></textarea></div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Simpan</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
