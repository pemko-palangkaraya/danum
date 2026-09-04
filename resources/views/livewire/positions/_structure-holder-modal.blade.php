@if($showHolderForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" wire:click.self="$set('showHolderForm', false)">
        <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
            @php($holderPosition = $positions->firstWhere('id', $holderPositionId))
            <div class="flex items-start justify-between gap-4">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pemangku Jabatan</p><h2 class="mt-1 text-lg font-semibold text-slate-900">{{ $holderPosition?->name }}</h2><p class="mt-1 text-sm text-slate-500">Tetapkan pejabat/pelaksana sekaligus dasar SK pengangkatannya.</p></div>
                <button type="button" wire:click="$set('showHolderForm', false)" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup">×</button>
            </div>
            <form wire:submit="saveHolder" class="mt-6 space-y-4">
                <div><label class="text-sm font-medium text-slate-700">Pemangku</label><select wire:model="holderUserId" class="form-select mt-1 w-full"><option value="">Pilih pengguna...</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}{{ $user->email ? ' — '.$user->email : '' }}</option>@endforeach</select>@error('holderUserId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="text-sm font-medium text-slate-700">Status Penugasan</label><select wire:model="holderAssignmentStatus" class="form-select mt-1 w-full">@foreach($assignmentStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select>@error('holderAssignmentStatus')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-medium text-slate-700">Mulai Menjabat</label><input type="date" wire:model="holderStartedAt" class="form-control mt-1 w-full">@error('holderStartedAt')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                </div>
                <div><label class="text-sm font-medium text-slate-700">Nomor SK</label><input type="text" wire:model="holderAppointmentNumber" class="form-control mt-1 w-full" placeholder="Contoh: 800.1.3.3/123/BKPSDM/2026"><p class="mt-1 text-xs text-slate-500">Nomor surat keputusan pengangkatan/penunjukan.</p>@error('holderAppointmentNumber')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-medium text-slate-700">Dokumen SK</label><input type="file" wire:model="holderAppointmentDocument" accept="application/pdf" class="form-control mt-1 w-full"><p class="mt-1 text-xs text-slate-500">PDF maksimal 10 MB. Dokumen disimpan secara privat.</p>@error('holderAppointmentDocument')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div wire:loading wire:target="holderAppointmentDocument" class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700">Mengunggah dokumen...</div>
                <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="$set('showHolderForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button><button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"><span wire:loading.remove wire:target="saveHolder">Simpan Pemangku</span><span wire:loading wire:target="saveHolder">Menyimpan...</span></button></div>
            </form>
        </div>
    </div>
@endif
