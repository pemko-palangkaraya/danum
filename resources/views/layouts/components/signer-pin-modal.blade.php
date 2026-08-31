<div
    x-data="{ open: false, action: '', id: '', note: '', title: '', description: '', pin: '', error: '', submit() { const pin = this.pin.trim(); if (!/^\d{6}$/.test(pin)) { this.error = 'PIN harus terdiri dari 6 digit.'; return; } Livewire.dispatch('signer-pin-submitted', { action: this.action, id: this.id, note: this.note, pin }); this.open = false; this.pin = ''; this.error = ''; } }"
    x-on:signer-pin-required.window="action = $event.detail.action; id = $event.detail.id; note = $event.detail.note; title = $event.detail.title; description = $event.detail.description; pin = ''; error = ''; open = true; $nextTick(() => $refs.pin?.focus())"
    x-on:signer-pin-invalid.window="error = 'PIN tanda tangan tidak valid.'; open = true; $nextTick(() => $refs.pin?.focus())"
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
            <label class="text-sm font-medium text-slate-700">PIN Tanda Tangan <span class="text-red-600">*</span></label>
            <input x-ref="pin" x-model="pin" x-on:input="pin = pin.replace(/\D/g, '').slice(0, 6); error = ''" x-on:keydown.enter.prevent="submit()" type="password" inputmode="numeric" autocomplete="off" maxlength="6" class="form-control mt-1 tracking-[0.35em]" placeholder="••••••">
            <p x-show="error" x-text="error" class="mt-1 text-xs text-red-600"></p>
            <p class="mt-2 text-xs text-slate-400">PIN ini adalah faktor otorisasi tanda tangan Anda, bukan password akun.</p>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" x-on:click="open = false; pin = ''; error = ''" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
            <button type="button" x-on:click="submit()" :disabled="pin.length !== 6" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">Tanda Tangani</button>
        </div>
    </div>
</div>
