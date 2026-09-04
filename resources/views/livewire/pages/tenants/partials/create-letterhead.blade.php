<x-ui.card>
    <x-slot:header>
        <h2 class="text-sm font-semibold text-slate-900">Letterhead / Kop Surat</h2>
        <p class="mt-1 text-xs text-slate-500">Kop ini akan digunakan oleh seluruh surat yang dibuat tenant ini.</p>
    </x-slot:header>

    <div class="grid gap-5 sm:grid-cols-[minmax(0,1fr)_280px]">
        <x-ui.field label="Upload Kop Surat" for="tenant-letterhead" hint="PNG, JPG, JPEG, atau WEBP. Maksimal 5 MB." error="{{ $errors->first('letterhead') }}" required>
            <input id="tenant-letterhead" type="file" wire:model="letterhead" accept=".png,.jpg,.jpeg,.webp" class="form-control w-full bg-white" />
        </x-ui.field>

        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-3">
            @if ($letterhead)
                <img src="{{ $letterhead->temporaryUrl() }}" alt="Letterhead preview" class="max-h-32 w-full object-contain">
            @else
                <div class="flex h-24 items-center justify-center text-center text-xs text-slate-400">Preview kop surat akan tampil di sini</div>
            @endif
        </div>
    </div>
</x-ui.card>
