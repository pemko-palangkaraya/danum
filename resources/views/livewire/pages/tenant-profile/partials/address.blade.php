<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
        <h2 class="text-sm font-semibold text-slate-900">Alamat</h2>
    </div>

    <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4 sm:p-6">
        <div>
            <label for="tenant-province" class="block text-sm font-medium text-slate-700">Provinsi</label>
            <input id="tenant-province" type="text" wire:model="province" @disabled(!$canUpdate) class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 disabled:cursor-not-allowed disabled:opacity-70">
            @error('province') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tenant-city" class="block text-sm font-medium text-slate-700">Kota/Kabupaten</label>
            <input id="tenant-city" type="text" wire:model="city" @disabled(!$canUpdate) class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 disabled:cursor-not-allowed disabled:opacity-70">
            @error('city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tenant-district" class="block text-sm font-medium text-slate-700">Kecamatan</label>
            <input id="tenant-district" type="text" wire:model="district" @disabled(!$canUpdate) class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 disabled:cursor-not-allowed disabled:opacity-70">
            @error('district') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tenant-village" class="block text-sm font-medium text-slate-700">Kelurahan/Desa</label>
            <input id="tenant-village" type="text" wire:model="village" @disabled(!$canUpdate) class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 disabled:cursor-not-allowed disabled:opacity-70">
            @error('village') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2 lg:col-span-4">
            <label for="tenant-address" class="block text-sm font-medium text-slate-700">Alamat Lengkap</label>
            <textarea id="tenant-address" rows="3" wire:model="address" @disabled(!$canUpdate) class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 disabled:cursor-not-allowed disabled:opacity-70"></textarea>
            @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</section>
