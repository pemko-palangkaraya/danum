@if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit Jabatan' : 'Tambah Jabatan' }}</h2>
            <p class="mt-1 text-sm text-slate-500">Master jabatan dibuat oleh Super Admin.</p>

            <div class="mt-5 space-y-4">
                @if($isSuperAdmin)
                    <div>
                        <label class="text-sm font-medium text-slate-700">Organisasi</label>
                        <select wire:model="selectedTenantId" class="form-select mt-1">
                            <option value="">Pilih organisasi...</option>
                            @foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach
                        </select>
                        @error('selectedTenantId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div>
                    <label class="text-sm font-medium text-slate-700">Kode Jabatan</label>
                    <input wire:model="code" type="text" placeholder="Contoh: LURAH" class="form-control mt-1">
                    @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Nama Jabatan</label>
                    <input wire:model="name" type="text" placeholder="Contoh: Lurah" class="form-control mt-1">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Deskripsi <span class="font-normal text-slate-400">(opsional)</span></label>
                    <textarea wire:model="description" rows="3" class="form-textarea mt-1"></textarea>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Status</label>
                    <select wire:model="status" class="form-select mt-1">
                        <option value="active">Aktif</option>
                        <option value="inactive">Tidak Aktif</option>
                    </select>
                </div>

                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                    <input wire:model="can_sign" type="checkbox" class="mt-0.5 rounded border-slate-300">
                    <span><span class="block text-sm font-semibold">Jabatan dapat menandatangani</span><span class="block text-xs text-slate-500">Pemegang aktif jabatan ini dapat dipilih sebagai penanda tangan.</span></span>
                </label>

                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                    <input wire:model="can_validate" type="checkbox" class="mt-0.5 rounded border-slate-300">
                    <span><span class="block text-sm font-semibold">Jabatan dapat melakukan verifikasi</span><span class="block text-xs text-slate-500">Pemegang aktif jabatan ini dapat dipilih sebagai verifikator.</span></span>
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button wire:click="$set('showForm', false)" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button>
                <button wire:click="save" type="button" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Simpan</button>
            </div>
        </div>
    </div>
@endif
