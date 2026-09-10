<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
        <h2 class="text-sm font-semibold text-slate-900">Identitas</h2>
    </div>

    <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
        <div class="sm:col-span-2">
            <label for="tenant-name" class="block text-sm font-medium text-slate-700">Nama Organisasi</label>
            <input id="tenant-name" type="text" wire:model="name" @disabled(!$canUpdate) class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 disabled:cursor-not-allowed disabled:opacity-70">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tenant-phone" class="block text-sm font-medium text-slate-700">Telepon</label>
            <input id="tenant-phone" type="text" wire:model="phone" @disabled(!$canUpdate) class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 disabled:cursor-not-allowed disabled:opacity-70">
            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tenant-email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="tenant-email" type="email" wire:model="email" @disabled(!$canUpdate) class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 disabled:cursor-not-allowed disabled:opacity-70">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</section>
