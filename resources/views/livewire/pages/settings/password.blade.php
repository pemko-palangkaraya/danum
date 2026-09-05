<div class="space-y-6">
    <div>
        <p class="text-sm text-slate-500">Settings</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Keamanan Akun</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola password untuk akun {{ auth()->user()->email }}.</p>
    </div>

    <form wire:submit="save" class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Ganti Password</h2>
            <p class="mt-1 text-sm text-slate-500">Masukkan password saat ini sebelum membuat password baru.</p>
        </div>

        <div class="mt-6 space-y-5">
            <div>
                <label class="text-sm font-medium text-slate-700">Password Saat Ini <span class="text-red-500">*</span></label>
                <input wire:model="currentPassword" type="password" autocomplete="current-password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                @error('currentPassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Password Baru <span class="text-red-500">*</span></label>
                <input wire:model="newPassword" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                @error('newPassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Konfirmasi Password Baru <span class="text-red-500">*</span></label>
                <input wire:model="newPasswordConfirmation" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                @error('newPasswordConfirmation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button>
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Simpan Password</button>
        </div>
    </form>
</div>
