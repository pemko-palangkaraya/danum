@if($showHolderForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showHolderForm', false)">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <h2 class="text-lg font-semibold">Tetapkan Pemegang Jabatan</h2>
            <p class="mt-1 text-sm text-slate-500">Data pejabat aktif dimuat otomatis. Saat diganti, riwayat pejabat sebelumnya tetap tersimpan.</p>

            <div class="mt-5 space-y-4">
                <div>
                    <label class="text-sm font-medium text-slate-700">Pejabat</label>
                    <select wire:model="holderUserId" class="form-select mt-1">
                        <option value="">Pilih user...</option>
                        @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>@endforeach
                    </select>
                    @error('holderUserId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Tanggal Mulai</label>
                    <input wire:model="holderStartedAt" type="date" class="form-control mt-1">
                    @error('holderStartedAt')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button wire:click="$set('showHolderForm', false)" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button>
                <button wire:click="saveHolder" type="button" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Tetapkan</button>
            </div>
        </div>
    </div>
@endif
