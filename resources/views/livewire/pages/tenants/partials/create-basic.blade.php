<x-ui.card>
    <x-slot:header>
        <h2 class="text-sm font-semibold text-slate-900">Basic Information</h2>
        <p class="mt-1 text-xs text-slate-500">Informasi dasar organisasi.</p>
    </x-slot:header>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.input wire:model="code" label="Code" id="tenant-code" maxlength="50" placeholder="Contoh: MKB" error="{{ $errors->first('code') }}" required />
        <x-ui.input wire:model="name" label="Name" id="tenant-name" maxlength="150" placeholder="Nama organisasi" error="{{ $errors->first('name') }}" required />
        <x-ui.field label="Kategori Organisasi" for="tenant-category" error="{{ $errors->first('tenant_category_id') }}" required>
            <select id="tenant-category" wire:model="tenant_category_id" class="form-select w-full">
                <option value="">Pilih kategori</option>
                @foreach (TenantCategory::query()->where('is_active', true)->orderBy('sort_order')->get() as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </x-ui.field>
    </div>
</x-ui.card>
