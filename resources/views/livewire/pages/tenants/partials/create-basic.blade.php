<x-ui.card>
    <x-slot:header>
        <h2 class="text-sm font-semibold text-slate-900">Basic Information</h2>
        <p class="mt-1 text-xs text-slate-500">Informasi dasar organisasi dan hubungan hierarkinya.</p>
    </x-slot:header>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.input wire:model="code" label="Code" id="tenant-code" maxlength="50" placeholder="Contoh: KEL-LANGKAI" error="{{ $errors->first('code') }}" required />
        <x-ui.input wire:model="name" label="Name" id="tenant-name" maxlength="150" placeholder="Nama organisasi" error="{{ $errors->first('name') }}" required />

        <x-ui.field label="Kategori Organisasi" for="tenant-category" error="{{ $errors->first('tenant_category_id') }}" required>
            <select id="tenant-category" wire:model.live="tenant_category_id" class="form-select w-full">
                <option value="">Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.field label="Parent Tenant" for="tenant-parent" error="{{ $errors->first('parent_tenant_id') }}">
            <select id="tenant-parent" wire:model.live="parent_tenant_id" @disabled($tenant_category_id === '' || $parentTenants->isEmpty()) class="form-select w-full disabled:bg-slate-50 disabled:text-slate-400">
                <option value="">
                    @if($tenant_category_id === '')
                        Pilih kategori dahulu
                    @elseif($parentTenants->isEmpty())
                        Tidak membutuhkan parent
                    @else
                        Pilih parent tenant
                    @endif
                </option>
                @foreach ($parentTenants as $parentTenant)
                    <option value="{{ $parentTenant->id }}">
                        {{ $parentTenant->name }} — {{ $parentTenant->district !== 'Pusat Pemerintahan' ? $parentTenant->district : $parentTenant->city }}
                    </option>
                @endforeach
            </select>
            @if($tenant_category_id !== '' && $parentTenants->isNotEmpty())
                <p class="mt-1.5 text-xs text-slate-500">Kecamatan berada di bawah Pemerintah Kota; Kelurahan/Desa berada di bawah Kecamatan.</p>
            @endif
        </x-ui.field>
    </div>
</x-ui.card>
