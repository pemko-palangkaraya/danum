<div
    x-data="{
        open: false,
        action: '',
        id: '',
        title: '',
        description: '',
        note: '',
        submit() {
            const note = this.note.trim();
            if (!note) return;

            Livewire.dispatch('workflow-note-submitted', {
                action: this.action,
                id: this.id,
                note,
            });

            this.open = false;
            this.note = '';
        },
        close() {
            this.open = false;
            this.note = '';
        },
    }"
    x-on:workflow-note-required.window="
        action = $event.detail.action;
        id = $event.detail.id;
        title = $event.detail.title;
        description = $event.detail.description;
        note = '';
        open = true;
        $nextTick(() => $refs.note?.focus())
    "
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/40 p-4"
    x-on:keydown.escape.window="close()"
    x-on:click.self="close()"
>
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" x-show="open" x-transition>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900" x-text="title"></h2>
                <p class="mt-1 text-sm text-slate-500" x-text="description"></p>
            </div>

            <button
                type="button"
                x-on:click="close()"
                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                aria-label="Tutup"
            >
                ✕
            </button>
        </div>

        <div class="mt-5">
            <label class="text-sm font-medium text-slate-700">
                Catatan <span class="text-red-600">*</span>
            </label>

            <textarea
                x-ref="note"
                x-model="note"
                x-on:keydown.ctrl.enter.prevent="submit()"
                x-on:keydown.meta.enter.prevent="submit()"
                rows="5"
                maxlength="2000"
                class="form-textarea mt-1"
                placeholder="Tuliskan hasil pemeriksaan atau catatan penandatanganan..."
            ></textarea>

            <p class="mt-1 text-xs text-slate-400">
                Catatan wajib diisi dan menjadi bagian dari jejak audit surat.
            </p>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button
                type="button"
                x-on:click="close()"
                class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Batal
            </button>

            <button
                type="button"
                x-on:click="submit()"
                :disabled="!note.trim()"
                class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Simpan &amp; Lanjutkan
            </button>
        </div>
    </div>
</div>
