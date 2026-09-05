<div
    x-data="{ open: false, id: '', note: '', pdfUrl: '', close() { this.open = false; } }"
    x-on:issue-review-required.window="id = $event.detail.id; note = $event.detail.note; pdfUrl = $event.detail.pdfUrl; open = true"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/50 p-4"
    x-on:keydown.escape.window="close()"
    x-on:click.self="close()"
>
    <div class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" x-show="open" x-transition>
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Periksa & Terbitkan Surat</h2>
                <p class="mt-1 text-sm text-slate-500">Periksa isi dan format PDF sebelum surat diterbitkan.</p>
            </div>
            <button type="button" x-on:click="close()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">✕</button>
        </div>

        <div class="grid min-h-0 flex-1 gap-5 overflow-hidden p-5 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="min-h-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                <iframe :src="pdfUrl" title="Preview PDF surat" class="h-[60vh] w-full lg:h-[65vh]"></iframe>
            </div>
            <div class="flex min-h-0 flex-col">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Konfirmasi penerbitan</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Pastikan isi, nomor, tanggal, kop, tata letak, dan seluruh halaman surat sudah sesuai.</p>
                    <label class="mt-4 flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" x-model="confirmed" class="mt-0.5 rounded border-slate-300">
                        <span>Saya sudah memeriksa isi dan format surat.</span>
                    </label>
                </div>
                <div class="mt-auto flex flex-col gap-2 pt-4">
                    <button type="button" x-on:click="Livewire.dispatch('issue-review-submitted', { id, note, mode: 'tte' }); close()" :disabled="!confirmed" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">Issue & Lanjut TTE</button>
                    <button type="button" x-on:click="Livewire.dispatch('issue-review-submitted', { id, note, mode: 'wet' }); close()" :disabled="!confirmed" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Issue & Selesai</button>
                    <button type="button" x-on:click="close()" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50">Batal</button>
                </div>
            </div>
        </div>
    </div>
</div>
