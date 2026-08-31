<div
    x-data="{ open: false, url: '' }"
    x-on:signing-pin-missing.window="url = $event.detail?.url ?? '{{ route('settings.signing-pin') }}'; open = true"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/40 p-4"
    x-on:keydown.escape.window="open = false"
    x-on:click.self="open = false"
>
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" x-show="open" x-transition>
        <div class="border-b border-slate-100 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.8 2.7 17a2 2 0 0 0 1.73 3h15.14a2 2 0 0 0 1.73-3L13.7 3.8a2 2 0 0 0-3.4 0Z" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">PIN Tanda Tangan Belum Diatur</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Anda memiliki kewenangan untuk menerbitkan surat, tetapi PIN tanda tangan belum dikonfigurasi. Atur PIN terlebih dahulu sebelum melanjutkan proses tanda tangan elektronik.</p>
                </div>
                <button type="button" x-on:click="open = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">✕</button>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-5">
            <button type="button" x-on:click="open = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Nanti</button>
            <a x-bind:href="url" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Atur PIN Sekarang</a>
        </div>
    </div>
</div>
