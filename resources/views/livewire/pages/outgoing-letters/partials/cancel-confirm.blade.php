@if($showCancelConfirm)
    <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="closeCancel">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="cancel-letter-title">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.8 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3L13.7 3.8a2 2 0 0 0-3.4 0Z" />
                    </svg>
                </div>
                <div>
                    <h2 id="cancel-letter-title" class="text-lg font-semibold text-slate-900">Batalkan draft surat?</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Draft ini akan ditandai sebagai dibatalkan dan tidak dapat dikirim untuk verifikasi lagi.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button wire:click="closeCancel" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Jangan Batalkan
                </button>
                <button wire:click="cancelLetter('{{ $cancelId }}')" type="button" class="rounded-xl bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800">
                    Ya, Batalkan
                </button>
            </div>
        </div>
    </div>
@endif
