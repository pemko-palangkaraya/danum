<div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showRejectForm', false)">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-lg font-semibold text-slate-900">Tolak Surat</h2>
        <p class="mt-1 text-sm text-slate-500">Surat akan dikembalikan kepada pembuat dan dapat diperbaiki. Alasan penolakan wajib dicatat.</p>
        <div class="mt-5">
            <label class="text-sm font-medium text-slate-700">Alasan Penolakan</label>
            <textarea wire:model="rejectReason" rows="5" class="form-textarea mt-1" placeholder="Jelaskan bagian yang perlu diperbaiki..."></textarea>
            @error('rejectReason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button wire:click="$set('showRejectForm', false)" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button>
            <button wire:click="rejectLetter" type="button" class="rounded-xl bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800">Tolak Surat</button>
        </div>
    </div>
</div>