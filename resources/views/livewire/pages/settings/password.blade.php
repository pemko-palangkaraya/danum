<div class="space-y-6">
    <div>
        <p class="text-sm text-slate-500">Settings</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Keamanan Akun</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola password untuk akun {{ auth()->user()->email }}.</p>
    </div>

    <form wire:submit="save" x-data="{ showCurrent: false, showNew: false, showConfirmation: false }" class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Ganti Password</h2>
            <p class="mt-1 text-sm text-slate-500">Masukkan password saat ini sebelum membuat password baru.</p>
        </div>

        <div class="mt-6 space-y-5">
            <div>
                <label for="current-password" class="text-sm font-medium text-slate-700">Password Saat Ini <span class="text-red-500">*</span></label>
                <div class="relative mt-2">
                    <input
                        id="current-password"
                        wire:model="currentPassword"
                        x-bind:type="showCurrent ? 'text' : 'password'"
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-200 py-2.5 pl-3.5 pr-11 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >
                    <button
                        type="button"
                        x-on:click="showCurrent = !showCurrent"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-slate-700 focus:outline-none focus:text-slate-700"
                        :aria-label="showCurrent ? 'Sembunyikan password' : 'Tampilkan password'"
                    >
                        <svg x-show="!showCurrent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.423 7.51 7.36 5 12 5c4.64 0 8.577 2.51 9.964 6.678.06.18.06.376 0 .644C20.577 16.49 16.64 19 12 19c-4.64 0-8.577-2.51-9.964-6.678Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg x-cloak x-show="showCurrent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 2.036 12.322a1.012 1.012 0 0 0 0 .644C3.423 17.49 7.36 20 12 20c1.54 0 3.003-.332 4.317-.93M6.228 6.228A10.45 10.45 0 0 1 12 4c4.64 0 8.577 2.51 9.964 6.678.06.18.06.376 0 .644a10.52 10.52 0 0 1-4.027 5.03M6.228 6.228 3 3m3.228 3.228 14.544 14.544M14.12 14.12a3 3 0 0 1-4.24-4.24" />
                        </svg>
                    </button>
                </div>
                @error('currentPassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="new-password" class="text-sm font-medium text-slate-700">Password Baru <span class="text-red-500">*</span></label>
                <div class="relative mt-2">
                    <input
                        id="new-password"
                        wire:model="newPassword"
                        x-bind:type="showNew ? 'text' : 'password'"
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-200 py-2.5 pl-3.5 pr-11 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >
                    <button
                        type="button"
                        x-on:click="showNew = !showNew"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-slate-700 focus:outline-none focus:text-slate-700"
                        :aria-label="showNew ? 'Sembunyikan password' : 'Tampilkan password'"
                    >
                        <svg x-show="!showNew" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.423 7.51 7.36 5 12 5c4.64 0 8.577 2.51 9.964 6.678.06.18.06.376 0 .644C20.577 16.49 16.64 19 12 19c-4.64 0-8.577-2.51-9.964-6.678Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg x-cloak x-show="showNew" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 2.036 12.322a1.012 1.012 0 0 0 0 .644C3.423 17.49 7.36 20 12 20c1.54 0 3.003-.332 4.317-.93M6.228 6.228A10.45 10.45 0 0 1 12 4c4.64 0 8.577 2.51 9.964 6.678.06.18.06.376 0 .644a10.52 10.52 0 0 1-4.027 5.03M6.228 6.228 3 3m3.228 3.228 14.544 14.544M14.12 14.12a3 3 0 0 1-4.24-4.24" />
                        </svg>
                    </button>
                </div>
                @error('newPassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="new-password-confirmation" class="text-sm font-medium text-slate-700">Konfirmasi Password Baru <span class="text-red-500">*</span></label>
                <div class="relative mt-2">
                    <input
                        id="new-password-confirmation"
                        wire:model="newPasswordConfirmation"
                        x-bind:type="showConfirmation ? 'text' : 'password'"
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-200 py-2.5 pl-3.5 pr-11 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >
                    <button
                        type="button"
                        x-on:click="showConfirmation = !showConfirmation"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-slate-700 focus:outline-none focus:text-slate-700"
                        :aria-label="showConfirmation ? 'Sembunyikan password' : 'Tampilkan password'"
                    >
                        <svg x-show="!showConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.423 7.51 7.36 5 12 5c4.64 0 8.577 2.51 9.964 6.678.06.18.06.376 0 .644C20.577 16.49 16.64 19 12 19c-4.64 0-8.577-2.51-9.964-6.678Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg x-cloak x-show="showConfirmation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 2.036 12.322a1.012 1.012 0 0 0 0 .644C3.423 17.49 7.36 20 12 20c1.54 0 3.003-.332 4.317-.93M6.228 6.228A10.45 10.45 0 0 1 12 4c4.64 0 8.577 2.51 9.964 6.678.06.18.06.376 0 .644a10.52 10.52 0 0 1-4.027 5.03M6.228 6.228 3 3m3.228 3.228 14.544 14.544M14.12 14.12a3 3 0 0 1-4.24-4.24" />
                        </svg>
                    </button>
                </div>
                @error('newPasswordConfirmation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button>
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Simpan Password</button>
        </div>
    </form>
</div>
