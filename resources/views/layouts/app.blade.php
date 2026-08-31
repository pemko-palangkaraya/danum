<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'DANUM' }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-ui.toast />
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen">
        @include('layouts.components.mobile-header')
        <div class="min-h-screen lg:flex">
            <div class="lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:w-64">
                @include('layouts.components.sidebar')
            </div>
            <div class="min-w-0 flex-1 lg:ml-64 lg:h-screen lg:overflow-y-auto lg:pt-20">
                @include('layouts.components.topbar')
                <main>
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </div>

    @include('layouts.components.workflow-note-modal')

    <div
        x-data="{ open: false, action: '', id: '', note: '', title: '', description: '', pin: '', error: '', submit() { const pin = this.pin.trim(); if (!/^\d{6}$/.test(pin)) { this.error = 'PIN harus terdiri dari 6 digit.'; return; } Livewire.dispatch('signer-pin-submitted', { action: this.action, id: this.id, note: this.note, pin }); this.open = false; this.pin = ''; this.error = ''; } }"
        x-on:signer-pin-required.window="action = $event.detail.action; id = $event.detail.id; note = $event.detail.note; title = $event.detail.title; description = $event.detail.description; pin = ''; error = ''; open = true; $nextTick(() => $refs.pin?.focus())"
        x-on:signer-pin-invalid.window="error = 'PIN tanda tangan tidak valid.'; open = true; $nextTick(() => $refs.pin?.focus())"
        x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/40 p-4"
        x-on:keydown.escape.window="open = false; pin = ''; error = ''" x-on:click.self="open = false; pin = ''; error = ''"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" x-show="open" x-transition>
            <div class="flex items-start justify-between gap-4">
                <div><h2 class="text-lg font-semibold text-slate-900" x-text="title"></h2><p class="mt-1 text-sm text-slate-500" x-text="description"></p></div>
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
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Anda memiliki kewenangan untuk menerbitkan surat, tetapi PIN tanda tangan belum dikonfigurasi. Atur PIN terlebih dahulu sebelum melanjutkan proses tanda tangan elektronik.
                        </p>
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

    @livewireScripts
</body>
</html>
