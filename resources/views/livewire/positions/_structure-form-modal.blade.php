@if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            @php($editingPosition = $positions->firstWhere('id', $editingPositionId))
            <div class="flex items-start justify-between gap-4">
                <div><h2 class="text-lg font-semibold text-slate-900">Atur Struktur Jabatan</h2><p class="mt-1 text-sm text-slate-500">{{ $editingPosition?->name }}</p></div>
                <button type="button" wire:click="$set('showForm', false)" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup">×</button>
            </div>
            <form wire:submit="saveStructure" class="mt-6 space-y-4">
                <div><label class="text-sm font-medium text-slate-700">Jenis Jabatan</label><select wire:model="positionType" class="form-select mt-1 w-full">@foreach($positionTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select>@error('positionType')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-medium text-slate-700">Berada di bawah</label><select wire:model="parentPositionId" class="form-select mt-1 w-full"><option value="">— Tidak ada atasan —</option>@foreach($positions as $position)@if($position->id !== $editingPositionId)<option value="{{ $position->id }}">{{ $position->name }} ({{ $position->position_type?->label() }})</option>@endif @endforeach</select>@error('parentPositionId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-medium text-slate-700">Urutan dalam kelompok</label><input type="number" min="0" wire:model="sortOrder" class="form-control mt-1 w-full">@error('sortOrder')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <label class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-3"><input type="checkbox" wire:model="isRoot" class="mt-0.5 rounded border-slate-300"><span><span class="block text-sm font-semibold text-blue-900">Kepala organisasi</span><span class="block text-xs text-blue-700">Jabatan ini menjadi titik paling atas pada bagan dan tidak memiliki atasan.</span></span></label>
                <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button><button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"><span wire:loading.remove>Simpan Struktur</span><span wire:loading>Menyimpan...</span></button></div>
            </form>
        </div>
    </div>
@endif
