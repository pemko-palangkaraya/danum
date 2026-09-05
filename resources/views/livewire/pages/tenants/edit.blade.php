<div class="space-y-6">
    <x-ui.page-header
        title="Edit Tenant"
        description="Perbarui informasi tenant, hubungan wilayah, kop surat, dan administrator."
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
                <h2 class="text-sm font-semibold text-slate-900">Location</h2>
                <p class="mt-1 text-xs text-slate-500">Lokasi administratif tenant. Untuk tenant wilayah, nilai mengikuti parent.</p>
            </x-slot:header>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.input wire:model="province" label="Province" id="tenant-province" maxlength="100" error="{{ $errors->first('province') }}" required />
                <x-ui.input wire:model="city" label="City" id="tenant-city" maxlength="100" error="{{ $errors->first('city') }}" required />
                <x-ui.input wire:model="district" label="District" id="tenant-district" maxlength="100" error="{{ $errors->first('district') }}" required />
                <x-ui.input wire:model="village" label="Village" id="tenant-village" maxlength="100" error="{{ $errors->first('village') }}" required />
                <div class="sm:col-span-2 lg:col-span-4">
                    <x-ui.field label="Address" for="tenant-address" error="{{ $errors->first('address') }}">
                        <textarea id="tenant-address" wire:model="address" rows="3" class="form-textarea w-full"></textarea>
                    </x-ui.field>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <h2 class="text-sm font-semibold text-slate-900">Contact & Leadership</h2>
                <p class="mt-1 text-xs text-slate-500">Informasi kontak dan pimpinan organisasi.</p>
            </x-slot:header>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input wire:model="phone" label="Phone" id="tenant-phone" maxlength="30" error="{{ $errors->first('phone') }}" />
                <x-ui.input wire:model="email" label="Email" id="tenant-email" type="email" maxlength="150" error="{{ $errors->first('email') }}" />
                <x-ui.input wire:model="head_name" label="Head Name" id="tenant-head-name" maxlength="150" error="{{ $errors->first('head_name') }}" />
                <x-ui.input wire:model="head_title" label="Head Title" id="tenant-head-title" maxlength="100" error="{{ $errors->first('head_title') }}" />
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <h2 class="text-sm font-semibold text-slate-900">Letterhead / Kop Surat</h2>
                <p class="mt-1 text-xs text-slate-500">Kop surat yang digunakan tenant.</p>
            </x-slot:header>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.field label="Upload Kop Surat" for="letterhead" error="{{ $errors->first('letterhead') }}">
                    <input id="letterhead" type="file" wire:model="letterhead" accept="image/png,image/jpeg,image/webp" class="form-input w-full" />
                    <p class="mt-1.5 text-xs text-slate-500">PNG, JPG/JPEG, atau WEBP. Maksimal 4 MB.</p>
                    <div wire:loading wire:target="letterhead" class="mt-2 text-xs text-slate-500">Uploading...</div>
                </x-ui.field>
                <div>
                    <p class="text-sm font-medium text-slate-700">Preview Kop Aktif</p>
                    <div class="mt-2 flex min-h-32 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4">
                        @if ($letterhead)
                            <img src="{{ $letterhead->temporaryUrl() }}" alt="Preview kop surat baru" class="max-h-40 max-w-full object-contain">
                        @elseif ($currentLetterhead)
                            <img src="{{ $currentLetterhead }}" alt="Kop surat tenant" class="max-h-40 max-w-full object-contain">
                        @else
                            <span class="text-xs text-slate-400">Belum ada kop surat.</span>
                        @endif
                    </div>
                </div>
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
