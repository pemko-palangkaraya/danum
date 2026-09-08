<x-ui.card padding="p-5 sm:p-6">
    <h2 class="text-sm font-semibold text-slate-900">{{ $editingUserId ? 'Edit User' : 'Add User' }}</h2>
    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <x-ui.input wire:model="name" label="Name" id="user-name" name="name" autocomplete="name" error="{{ $errors->first('name') }}" required />
        <x-ui.input wire:model="nip" label="NIP" id="user-nip" name="nip" autocomplete="off" maxlength="18" placeholder="Nomor Induk Pegawai" error="{{ $errors->first('nip') }}" />
        <x-ui.input wire:model="pangkat" label="Pangkat" id="user-pangkat" name="pangkat" autocomplete="off" placeholder="Contoh: Pembina" error="{{ $errors->first('pangkat') }}" />
        <x-ui.input wire:model="golongan" label="Golongan" id="user-golongan" name="golongan" autocomplete="off" placeholder="Contoh: IV/a" error="{{ $errors->first('golongan') }}" />
        <x-ui.input wire:model="statusPegawai" label="Status Pegawai" id="user-status-pegawai" name="status_pegawai" autocomplete="off" placeholder="Contoh: PNS" error="{{ $errors->first('status_pegawai') }}" />
        <x-ui.input wire:model="tanggalMasuk" label="Tanggal Masuk" id="user-tanggal-masuk" name="tanggal_masuk" type="date" error="{{ $errors->first('tanggal_masuk') }}" />
        <x-ui.input wire:model="tanggalPensiun" label="Tanggal Pensiun" id="user-tanggal-pensiun" name="tanggal_pensiun" type="date" error="{{ $errors->first('tanggal_pensiun') }}" />
        <x-ui.input wire:model="email" label="Email / Login" id="user-email" name="email" type="email" autocomplete="email" error="{{ $errors->first('email') }}" required />
        <x-ui.field label="Role" for="user-role" :error="$errors->first('role') ?: $errors->first('custom_role_id')" hint="Custom role muncul setelah tenant dipilih dan hanya role yang berlaku untuk tenant tersebut.">
            <select id="user-role" wire:model.live="roleSelection" class="form-select w-full">
                @if(auth()->user()->isSuperAdmin())
                    <option value="tenant_user">Tenant User</option><option value="tenant_admin">Tenant Admin</option><option value="super_admin">Super Admin</option>
                @else
                    <option value="tenant_user">Tenant User</option>
                @endif
                @if($tenantId !== '')
                    @php($customRoles = $this->availableCustomRoles())
                    @if($customRoles->isNotEmpty())
                        <optgroup label="Custom Roles">@foreach($customRoles as $customRole)<option value="custom:{{ $customRole->id }}">{{ $customRole->name }} · {{ $customRole->scope === 'global' ? 'Global' : 'Tenant' }}</option>@endforeach</optgroup>
                    @endif
                @endif
            </select>
        </x-ui.field>
        <x-ui.field label="Tenant" for="user-tenant" :error="$errors->first('tenant_id')">
            <select id="user-tenant" wire:model.live="tenantId" class="form-select w-full"><option value="">Select tenant</option>@foreach ($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select>
        </x-ui.field>
        <x-ui.input wire:model="password" label="Password {{ $editingUserId ? '(optional)' : '' }}" id="user-password" type="password" autocomplete="new-password" error="{{ $errors->first('password') }}" :required="!$editingUserId" />
        <x-ui.field label="Status" for="user-status" :error="$errors->first('status')">
            <select id="user-status" wire:model="status" class="form-select w-full">@foreach (\App\Enums\UserStatus::cases() as $userStatus)<option value="{{ $userStatus->value }}">{{ ucfirst($userStatus->value) }}</option>@endforeach</select>
        </x-ui.field>
    </div>
    <x-ui.form-actions><x-ui.button wire:click="resetForm" variant="secondary">Cancel</x-ui.button><x-ui.button wire:click="save" variant="primary" loading="save">Save User</x-ui.button></x-ui.form-actions>
</x-ui.card>
