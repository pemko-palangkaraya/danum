<?php

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
    public string $role = 'tenant_user';
    public string $roleSelection = 'tenant_user';
    public string $tenantId = '';
    public string $status = UserStatus::ACTIVE->value;
    public ?string $customRoleId = null;
    public string $search = '';
    public string $filter = 'active';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::query()->with('customRole')->findOrFail($id);
        $this->authorize('update', $user);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->nip = (string) ($user->nip ?? '');
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->isSuperAdmin() ? 'super_admin' : ($user->effectiveRole()?->slug ?? 'tenant_user');
        $this->tenantId = (string) ($user->tenant_id ?? '');
        $this->status = $user->status->value;
        $this->customRoleId = $user->custom_role_id ? (string) $user->custom_role_id : null;
        $this->roleSelection = $this->customRoleId !== null ? 'custom:' . $this->customRoleId : $this->role;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function updatedRoleSelection(): void
    {
        if (str_starts_with($this->roleSelection, 'custom:')) {
            $customRoleId = substr($this->roleSelection, 7);
            $this->role = 'tenant_user';
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

        $query = Role::query()->where('is_system', false)->where('is_active', true);

        if (auth()->user()?->isSuperAdmin()) {
            return $query->orderBy('scope')->orderBy('name')->get();
        }

        return $query->where('scope', 'tenant')->where('tenant_id', $this->tenantId)->orderBy('name')->get();
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
        $auditLogService->record(action: 'signer_pin.updated', user: auth()->user(), auditable: $user, newValues: ['configured' => true], tenantId: $user->tenant_id);
        $this->closeSignerPin();
        $this->dispatch('toast', type: 'success', message: 'PIN tanda tangan berhasil disimpan.');
    }

    public function closeSignerPin(): void
    {
        $this->showSignerPin = false;
        $this->signerPinUserId = null;
        $this->signerPinUserName = '';
        $this->signerPin = '';
        $this->signerPinConfirmation = '';
        $this->resetValidation(['signerPin', 'signerPinConfirmation']);
    }

    public function save(UserService $userService): void
    {
        $customRole = null;

        if (str_starts_with($this->roleSelection, 'custom:')) {
            $customRoleId = (int) substr($this->roleSelection, 7);
            $customRole = Role::query()->whereKey($customRoleId)->where('is_system', false)->where('is_active', true)->where(function ($query) {
                if (auth()->user()?->isSuperAdmin()) {
                    $query->where('scope', 'global')->orWhere(fn ($tenant) => $tenant->where('scope', 'tenant')->where('tenant_id', $this->tenantId));
                } else {
                    $query->where('scope', 'tenant')->where('tenant_id', $this->tenantId);
                }
            })->firstOrFail();
            $this->role = 'tenant_user';
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
            'tenant_id' => in_array($this->role, ['tenant_user', 'tenant_admin'], true) ? $this->tenantId : null,
            'status' => $this->status,
            'custom_role_id' => $customRole?->id,
        ];

        if ($this->editingUserId) {
            $user = User::query()->findOrFail($this->editingUserId);
            $this->authorize('update', $user);
            if ($this->password === '') unset($data['password']);
            $data['user_id'] = $user->getKey();
            $rules = UpdateUserRequest::rulesFor($user);
            $rules['custom_role_id'] = ['nullable', 'integer', 'exists:roles,id'];
            $validated = Validator::make($data, $rules)->validate();
            if (isset($validated['password'])) $validated['password'] = Hash::make($validated['password']);
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
        $this->role = 'tenant_user';
        $this->roleSelection = 'tenant_user';
        $this->tenantId = '';
        $this->status = UserStatus::ACTIVE->value;
        $this->customRoleId = null;
        $this->resetValidation();
    }

    public function with(): array
    {
        $query = User::query()->with(['tenant', 'customRole'])->orderBy('name');

        if ($this->search !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', '%' . $search . '%')
                    ->orWhere('email', 'ilike', '%' . $search . '%')
                    ->orWhere('nip', 'ilike', '%' . $search . '%')
                    ->orWhereHas('tenant', fn ($tenant) => $tenant->where('name', 'ilike', '%' . $search . '%'));
            });
        }

        $query->when($this->filter === 'active', fn ($q) => $q->where('status', UserStatus::ACTIVE->value))
            ->when($this->filter === 'inactive', fn ($q) => $q->where('status', UserStatus::INACTIVE->value));

        return [
            'users' => $query->paginate($this->perPage),
            'tenants' => Tenant::query()->orderBy('name')->get(),
        ];
    }
};
?>

<div class="space-y-6">
    @include('livewire.pages.users.partials.header')

    @if ($showForm)
        @include('livewire.pages.users.partials.form')
    @endif

    @include('livewire.pages.users.partials.table')
    @include('livewire.pages.users.partials.signer-pin')
</div>
