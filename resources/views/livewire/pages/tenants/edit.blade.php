<div class="space-y-6">
    <x-ui.page-header
        title="Edit Tenant"
        description="Perbarui informasi dasar tenant, hubungan wilayah, dan administrator."
        :back-url="route('tenants.show', $tenantId)"
        back-label="Back to tenant"
    />

    <form wire:submit="save" class="space-y-6">
        <x-ui.card>
            <x-slot:header>
                <h2 class="text-sm font-semibold text-slate-900">Basic Information</h2>
                <p class="mt-1 text-xs text-slate-500">Informasi dasar organisasi dan hubungan hierarkinya.</p>
            </x-slot:header>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input wire:model="code" label="Code" id="tenant-code" maxlength="50" error="{{ $errors->first('code') }}" required />
                <x-ui.input wire:model="name" label="Name" id="tenant-name" maxlength="150" error="{{ $errors->first('name') }}" required />
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

        <x-ui.card>
            <x-slot:header>
                <h2 class="text-sm font-semibold text-slate-900">Initial Administrator</h2>
                <p class="mt-1 text-xs text-slate-500">Perbarui administrator yang terhubung dengan tenant ini.</p>
            </x-slot:header>
            @if ($administratorId)
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.input wire:model="administratorName" label="Name" id="administrator-name" maxlength="255" error="{{ $errors->first('administrator.name') }}" required />
                    <x-ui.input wire:model="administratorEmail" label="Email / Login" id="administrator-email" type="email" maxlength="255" error="{{ $errors->first('administrator.email') }}" required />
                    <x-ui.input wire:model="administratorPassword" label="New Password (optional)" id="administrator-password" type="password" autocomplete="new-password" error="{{ $errors->first('administrator.password') }}" />
                    <x-ui.input wire:model="administratorPasswordConfirmation" label="Confirm Password" id="administrator-password-confirmation" type="password" autocomplete="new-password" error="{{ $errors->first('administrator.password_confirmation') }}" />
                    <x-ui.field label="Status" for="administrator-status" error="{{ $errors->first('administrator.status') }}">
                        <select id="administrator-status" wire:model="administratorStatus" class="form-select w-full">
                            @foreach ($userStatuses as $userStatus)
                                <option value="{{ $userStatus->value }}">{{ ucfirst($userStatus->value) }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            @else
                <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Tenant ini belum memiliki administrator yang tercatat. Buat administrator melalui menu <strong>Manage Users</strong>.
                </div>
            @endif
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <h2 class="text-sm font-semibold text-slate-900">Status</h2>
            </x-slot:header>
            <x-ui.field label="Tenant Status" for="tenant-status" error="{{ $errors->first('status') }}" required>
                <select id="tenant-status" wire:model="status" class="form-select w-full sm:max-w-md">
                    <option value="">Select status</option>
                    @foreach ($statuses as $tenantStatus)
                        <option value="{{ $tenantStatus->value }}">{{ $tenantStatus->label() }}</option>
                    @endforeach
                </select>
            </x-ui.field>
        </x-ui.card>

        <x-ui.form-actions>
            <x-ui.button type="button" wire:click="cancel" variant="secondary">Cancel</x-ui.button>
            <x-ui.button type="submit" variant="primary" loading="save">Save Changes</x-ui.button>
        </x-ui.form-actions>
    </form>
</div>
