<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
        <h2 class="text-sm font-semibold text-slate-900">Pimpinan</h2>
    </div>

    <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
        <div>
            <label for="tenant-head-name" class="block text-sm font-medium text-slate-700">Nama Pimpinan</label>
            <input id="tenant-head-name" type="text" wire:model="headName" @disabled(!$canUpdate) class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 disabled:cursor-not-allowed disabled:opacity-70">
            @error('headName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tenant-head-title" class="block text-sm font-medium text-slate-700">Jabatan</label>
            <input id="tenant-head-title" type="text" wire:model="headTitle" @disabled(!$canUpdate) class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 disabled:cursor-not-allowed disabled:opacity-70">
            @error('headTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</section>
