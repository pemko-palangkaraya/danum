<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SignerPinService;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Livewire\Concerns\WithStandardTablePagination;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    use WithStandardTablePagination;
    public bool $showForm = false;
    public bool $showSignerPin = false;
    public ?int $editingUserId = null;
    public ?int $signerPinUserId = null;
    public string $signerPinUserName = '';
    public string $signerPin = '';
    public string $signerPinConfirmation = '';
    public string $name = '';
    public string $nip = '';
    public string $email = '';
    public string $password = '';
    public string $role = UserRole::TENANT_USER->value;
    public string $roleSelection = UserRole::TENANT_USER->value;
    public string $tenantId = '';
    public string $status = UserStatus::ACTIVE->value;
    public ?string $customRoleId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('update', $user);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->nip = (string) ($user->nip ?? '');
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role->value;
        $this->tenantId = (string) ($user->tenant_id ?? '');
        $this->status = $user->status->value;
        $this->customRoleId = $user->custom_role_id ? (string) $user->custom_role_id : null;
        $this->roleSelection = $this->customRoleId !== null
            ? 'custom:' . $this->customRoleId
            : $this->role;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function updatedRoleSelection(): void
    {
        if (str_starts_with($this->roleSelection, 'custom:')) {
            $customRoleId = substr($this->roleSelection, 7);
            $this->role = UserRole::TENANT_USER->value;
            $this->customRoleId = $customRoleId !== '' ? $customRoleId : null;
            return;
        }

        $this->role = $this->roleSelection;
        $this->customRoleId = null;
    }

    public function updatedTenantId(): void
    {
        if ($this->customRoleId !== null && ! $this->availableCustomRoles()->contains('id', (int) $this->customRoleId)) {
            $this->customRoleId = null;
            $this->roleSelection = $this->role;
        }
    }

    public function availableCustomRoles()
    {
        if ($this->tenantId === '') {
            return collect();
        }

        $query = Role::query()
            ->where('is_system', false)
            ->where('is_active', true);

        if (auth()->user()?->isSuperAdmin()) {
            return $query->orderBy('scope')->orderBy('name')->get();
        }

        return $query
            ->where('scope', 'tenant')
            ->where('tenant_id', $this->tenantId)
            ->orderBy('name')
            ->get();
    }

    public function openSignerPin(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('update', $user);
        $this->signerPinUserId = $user->id;
        $this->signerPinUserName = $user->name;
        $this->signerPin = '';
        $this->signerPinConfirmation = '';
        $this->resetValidation(['signerPin', 'signerPinConfirmation']);
        $this->showSignerPin = true;
    }

    public function saveSignerPin(SignerPinService $pinService, AuditLogService $auditLogService): void
    {
        $user = User::query()->findOrFail($this->signerPinUserId);
        $this->authorize('update', $user);
        $validated = Validator::make(
            ['signerPin' => $this->signerPin, 'signerPinConfirmation' => $this->signerPinConfirmation],
            ['signerPin' => ['required', 'digits:6'], 'signerPinConfirmation' => ['required', 'same:signerPin']],
            [
                'signerPin.required' => 'PIN wajib diisi.',
                'signerPin.digits' => 'PIN harus terdiri dari 6 digit.',
                'signerPinConfirmation.required' => 'Konfirmasi PIN wajib diisi.',
                'signerPinConfirmation.same' => 'Konfirmasi PIN tidak sama.',
            ]
        )->validate();

        $pinService->set($user, $validated['signerPin']);
        $auditLogService->record(
            action: 'signer_pin.updated',
            user: auth()->user(),
            auditable: $user,
            newValues: ['configured' => true],
            tenantId: $user->tenant_id,
        );
        $this->showSignerPin = false;
        $this->signerPin = '';
        $this->signerPinConfirmation = '';
        $this->dispatch('toast', type: 'success', message: 'PIN tanda tangan berhasil disimpan.');
    }

    public function save(UserService $userService): void
    {
        $customRole = null;

        if (str_starts_with($this->roleSelection, 'custom:')) {
            $customRoleId = (int) substr($this->roleSelection, 7);
            $customRole = Role::query()
                ->whereKey($customRoleId)
                ->where('is_system', false)
                ->where('is_active', true)
                ->where(function ($query) {
                    if (auth()->user()?->isSuperAdmin()) {
                        $query->where('scope', 'global')
                            ->orWhere(fn ($tenant) => $tenant->where('scope', 'tenant')->where('tenant_id', $this->tenantId));
                    } else {
                        $query->where('scope', 'tenant')->where('tenant_id', $this->tenantId);
                    }
                })
                ->firstOrFail();
            $this->role = UserRole::TENANT_USER->value;
            $this->customRoleId = (string) $customRole->id;
        } else {
            $this->customRoleId = null;
        }

        $data = [
            'name' => $this->name,
            'nip' => $this->nip,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'tenant_id' => in_array($this->role, [UserRole::TENANT_USER->value, UserRole::TENANT_ADMIN->value], true) ? $this->tenantId : null,
            'status' => $this->status,
            'custom_role_id' => $customRole?->id,
        ];

        if ($this->editingUserId) {
            $user = User::query()->findOrFail($this->editingUserId);
            $this->authorize('update', $user);
            if ($this->password === '') {
                unset($data['password']);
            }
            $data['user_id'] = $user->getKey();
            $rules = UpdateUserRequest::rulesFor($user);
            $rules['custom_role_id'] = ['nullable', 'integer', 'exists:roles,id'];
            $validated = Validator::make($data, $rules)->validate();
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }
            $userService->update($user, $validated);
        } else {
            $this->authorize('create', User::class);
            $rules = (new StoreUserRequest())->rules();
            $rules['custom_role_id'] = ['nullable', 'integer', 'exists:roles,id'];
            $validated = Validator::make($data, $rules)->validate();
            $validated['password'] = Hash::make($validated['password']);
            $userService->create($validated);
        }

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'User berhasil disimpan.');
    }

    public function toggleStatus(int $id, UserService $userService): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('update', $user);
        $userService->update($user, ['status' => $user->status === UserStatus::ACTIVE ? UserStatus::INACTIVE : UserStatus::ACTIVE]);
        $this->dispatch('toast', type: 'success', message: 'Status user diperbarui.');
    }

    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingUserId = null;
        $this->name = '';
        $this->nip = '';
        $this->email = '';
        $this->password = '';
        $this->role = UserRole::TENANT_USER->value;
        $this->roleSelection = UserRole::TENANT_USER->value;
        $this->tenantId = '';
        $this->status = UserStatus::ACTIVE->value;
        $this->customRoleId = null;
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'users' => User::query()->with(['tenant', 'customRole'])->orderBy('name')->paginate($this->perPage),
            'tenants' => Tenant::query()->orderBy('name')->get(),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Administration</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Users</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola administrator dan pengguna seluruh tenant.</p>
        </div>
        <button type="button" wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Add User</button>
    </div>

    @if ($showForm)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ $editingUserId ? 'Edit User' : 'Add User' }}</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><label class="text-sm font-medium text-slate-700">Name</label><input wire:model="name" type="text" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-medium text-slate-700">NIP</label><input wire:model="nip" type="text" maxlength="32" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm" placeholder="Nomor Induk Pegawai">@error('nip')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-medium text-slate-700">Email / Login</label><input wire:model="email" type="email" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Role</label>
                    <select wire:model.live="roleSelection" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm">
                        @if(auth()->user()->isSuperAdmin())
                            <option value="tenant_user">Tenant User</option>
                            <option value="tenant_admin">Tenant Admin</option>
                            <option value="super_admin">Super Admin</option>
                        @else
                            <option value="tenant_user">Tenant User</option>
                        @endif
                        @if($tenantId !== '')
                            @php($customRoles = $this->availableCustomRoles())
                            @if($customRoles->isNotEmpty())
                                <optgroup label="Custom Roles">
                                    @foreach($customRoles as $customRole)
                                        <option value="custom:{{ $customRole->id }}">{{ $customRole->name }} · {{ $customRole->scope === 'global' ? 'Global' : 'Tenant' }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endif
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Custom role muncul setelah tenant dipilih dan hanya role yang berlaku untuk tenant tersebut yang dapat dipilih.</p>
                    @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('custom_role_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label class="text-sm font-medium text-slate-700">Tenant</label><select wire:model.live="tenantId" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm"><option value="">Select tenant</option>@foreach ($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select>@error('tenant_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-medium text-slate-700">Password {{ $editingUserId ? '(optional)' : '' }}</label><input wire:model="password" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="text-sm font-medium text-slate-700">Status</label><select wire:model="status" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm">@foreach (UserStatus::cases() as $userStatus)<option value="{{ $userStatus->value }}">{{ ucfirst($userStatus->value) }}</option>@endforeach</select>@error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <div class="mt-5 flex justify-end gap-3"><button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button><button type="button" wire:click="save" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Save User</button></div>
        </div>
    @endif

    <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:block"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">User</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">NIP</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tenant</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Role</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse ($users as $user)
            <tr><td class="px-6 py-4"><div class="font-medium text-slate-900">{{ $user->name }}</div><div class="text-xs text-slate-500">{{ $user->email }}</div></td><td class="px-6 py-4 text-sm text-slate-700">{{ $user->nip ?: '-' }}</td><td class="px-6 py-4 text-sm text-slate-700">{{ $user->tenant?->name ?? 'System' }}</td><td class="px-6 py-4 text-sm text-slate-700">{{ $user->customRole?->name ?? $user->role->value }}</td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === UserStatus::ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $user->status->value }}</span></td><td class="px-6 py-4 text-right"><x-ui.user-actions :user="$user" /></td></tr>
        @empty
            <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada user.</td></tr>
        @endforelse
        </tbody></table></div></div>
    
    <div class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:hidden">
        @forelse ($users as $user)
            <div class="flex items-center justify-between gap-3 p-4">
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</div>
                    <div class="mt-0.5 truncate text-xs text-slate-500">{{ $user->email }}</div>
                </div>
                <div class="min-w-0 max-w-[42%] text-right">
                    <div class="truncate text-sm font-medium text-slate-800">{{ $user->customRole?->name ?? $user->role->value }}</div>
                    <div class="mt-1">
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $user->status === UserStatus::ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $user->status->value }}</span>
                    </div>
                </div>
                <div class="shrink-0"><x-ui.user-actions :user="$user" /></div>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-sm text-slate-500">Belum ada user.</div>
        @endforelse
    </div>

    <x-ui.table-footer :paginator="$users" label="users" />

    @if($showSignerPin)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showSignerPin', false)">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold text-slate-900">PIN Tanda Tangan</h2><p class="mt-1 text-sm text-slate-500">Credential signing untuk {{ $signerPinUserName }}.</p></div><button type="button" wire:click="$set('showSignerPin', false)" class="rounded-lg px-2 py-1 text-slate-400 hover:bg-slate-100">✕</button></div>
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">PIN ini berbeda dari password login. PIN hanya digunakan untuk mengotorisasi tindakan tanda tangan elektronik dan tidak dapat dilihat kembali setelah disimpan.</div>
                <div class="mt-5 space-y-4"><div><label class="text-sm font-medium text-slate-700">PIN baru</label><input wire:model="signerPin" type="password" inputmode="numeric" maxlength="6" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm tracking-[0.4em]" placeholder="••••••">@error('signerPin')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="text-sm font-medium text-slate-700">Konfirmasi PIN</label><input wire:model="signerPinConfirmation" type="password" inputmode="numeric" maxlength="6" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm tracking-[0.4em]" placeholder="••••••">@error('signerPinConfirmation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div>
                <div class="mt-6 flex justify-end gap-2"><button type="button" wire:click="$set('showSignerPin', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button type="button" wire:click="saveSignerPin" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Simpan PIN</button></div>
            </div>
        </div>
    @endif
</div>
