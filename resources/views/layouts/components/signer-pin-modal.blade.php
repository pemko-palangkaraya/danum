<!-- Deprecated compatibility view. The active BSrE flow uses signer-passphrase-modal.blade.php. -->
<div
    x-data="{ open: false, action: '', id: '', note: '', title: '', description: '', passphrase: '', error: '', submit() { const passphrase = this.passphrase.trim(); if (passphrase.length < 8) { this.error = 'Passphrase minimal 8 karakter.'; return; } Livewire.dispatch('signer-passphrase-submitted', { action: this.action, id: this.id, note: this.note, passphrase }); this.open = false; this.passphrase = ''; this.error = ''; } }"
    x-on:signer-pin-required.window="action = $event.detail.action; id = $event.detail.id; note = $event.detail.note; title = $event.detail.title; description = action === 'issue' ? 'Masukkan PIN untuk menerbitkan dan menandatangani surat secara elektronik.' : $event.detail.description; passphrase = ''; error = ''; open = true; $nextTick(() => $refs.pin?.focus())"
    x-on:signer-passphrase-invalid.window="error = 'PIN tanda tangan tidak valid.'; open = true; $nextTick(() => $refs.pin?.focus())"
    x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/40 p-4"
    x-on:keydown.escape.window="open = false; pin = ''; error = ''" x-on:click.self="open = false; pin = ''; error = ''"
>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" x-show="open" x-transition>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900" x-text="title"></h2>
                <p class="mt-1 text-sm text-slate-500" x-text="description"></p>
            </div>
            <button type="button" x-on:click="open = false; pin = ''; error = ''" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">✕</button>
        </div>
        <div class="mt-5">
            <label class="text-sm font-medium text-slate-700">Passphrase Tanda Tangan <span class="text-red-600">*</span></label>
            <input x-ref="pin" x-model="passphrase" x-on:input="pin = pin.slice(0, 255); error = ''" x-on:keydown.enter.prevent="submit()" type="password" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" maxlength="255" class="form-control mt-1 tracking-[0.35em]" placeholder="••••••">
            <p x-show="error" x-text="error" class="mt-1 text-xs text-red-600"></p>
            <p class="mt-2 text-xs text-slate-400">Passphrase digunakan hanya untuk proses penandatanganan dan tidak disimpan oleh DANUM.</p>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" x-on:click="open = false; pin = ''; error = ''" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
            <button type="button" x-on:click="submit()" :disabled="passphrase.length < 8" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">Tanda Tangani</button>
        </div>
    </div>
</div>
