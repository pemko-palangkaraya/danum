<div
    x-data="{
        open: false,
        id: '',
        note: '',
        pdfUrl: '',
        confirmed: false,
        busy: false,
        error: '',
        close() { if (!this.busy) { this.open = false; this.error = ''; } },
        openReview(event) {
            this.id = event.detail.id;
            this.note = event.detail.note;
            this.pdfUrl = event.detail.pdfUrl;
            this.confirmed = false;
            this.error = '';
            this.busy = false;
            this.open = true;
        },
        continueToTte() {
            if (!this.confirmed || this.busy) return;
            Livewire.dispatch('signer-pin-required', {
                action: 'issue',
                id: this.id,
                note: this.note,
                title: 'PIN Tanda Tangan',
                description: 'Surat akan diterbitkan sekaligus ditandatangani secara elektronik.',
            });
            this.close();
        },
        async issueWet() {
            if (!this.confirmed || this.busy) return;
            this.busy = true;
            this.error = '';
            try {
                const response = await fetch(`/api/outgoing-letters/${this.id}/issue`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ note: this.note, tte: false }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Surat gagal diterbitkan.');
                this.open = false;
                window.location.reload();
            } catch (exception) {
                this.error = exception.message;
                this.busy = false;
            }
        },
    }"
    x-on:issue-review-required.window="openReview($event)"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/50 p-4"
    x-on:keydown.escape.window="close()"
    x-on:click.self="close()"
>
    <div class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" x-show="open" x-transition>
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Periksa &amp; Terbitkan Surat</h2>
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

                <p x-show="error" x-text="error" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"></p>

                <div class="mt-auto flex flex-col gap-2 pt-4">
                    <button type="button" x-on:click="continueToTte()" :disabled="!confirmed || busy" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">Issue &amp; Lanjut TTE</button>
                    <button type="button" x-on:click="issueWet()" :disabled="!confirmed || busy" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Issue &amp; Selesai</button>
                    <button type="button" x-on:click="close()" :disabled="busy" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50">Batal</button>
                </div>
            </div>
        </div>
    </div>
</div>
