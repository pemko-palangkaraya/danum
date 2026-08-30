<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SignerPinService;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public int $perPage = 10;
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
    public string $status = UserStatus::ACTIVE->value;
    public string $roleSelection = UserRole::TENANT_USER->value;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isTenantAdmin(), 403);
        $this->authorize('viewAny', User::class);
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    private function tenantUserQuery()
    {
        return User::query()->where('tenant_id', auth()->user()->tenant_id);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = $this->tenantUserQuery()->with('customRole')->findOrFail($id);
        $this->authorize('update', $user);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->nip = (string) ($user->nip ?? '');
        $this->email = $user->email;
        $this->password = '';
        $this->status = $user->status->value;
        $this->roleSelection = UserRole::TENANT_USER->value;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function updatedRoleSelection(): void
    {
        $this->roleSelection = UserRole::TENANT_USER->value;
    }

    public function save(UserService $userService): void
    {
        $tenantId = (string) auth()->user()->tenant_id;

        $data = [
            'name' => $this->name,
            'nip' => $this->nip,
            'email' => $this->email,
            'password' => $this->password,
            'role' => UserRole::TENANT_USER->value,
            'tenant_id' => $tenantId,
            'status' => $this->status,
        ];

        if ($this->editingUserId) {
            $user = $this->tenantUserQuery()->findOrFail($this->editingUserId);
            $this->authorize('update', $user);

            if ($this->password === '') {
                unset($data['password']);
            }

            $data['user_id'] = $user->getKey();
            $validated = Validator::make($data, UpdateUserRequest::rulesFor($user))->validate();

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            // Do not touch custom_role_id here. The effective RBAC role is the source of truth.
            $userService->update($user, $validated);
        } else {
            $this->authorize('create', User::class);
            $validated = Validator::make($data, (new StoreUserRequest())->rules())->validate();
            $validated['password'] = Hash::make($validated['password']);
            $userService->create($validated);
        }

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Tenant user berhasil disimpan.');
    }

    public function toggleStatus(int $id, UserService $userService): void
    {
        $user = $this->tenantUserQuery()->findOrFail($id);
        $this->authorize('update', $user);

        $userService->update($user, [
            'status' => $user->status === UserStatus::ACTIVE
                ? UserStatus::INACTIVE
                : UserStatus::ACTIVE,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Status user diperbarui.');
    }

    public function openSignerPin(int $id): void
    {
        $user = $this->tenantUserQuery()->findOrFail($id);
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
        $user = $this->tenantUserQuery()->findOrFail($this->signerPinUserId);
        $this->authorize('update', $user);

        $validated = Validator::make(
            ['signerPin' => $this->signerPin, 'signerPinConfirmation' => $this->signerPinConfirmation],
            [
                'signerPin' => ['required', 'digits:6'],
                'signerPinConfirmation' => ['required', 'same:signerPin'],
            ],
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

    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingUserId = null;
        $this->name = '';
        $this->nip = '';
        $this->email = '';
        $this->password = '';
        $this->status = UserStatus::ACTIVE->value;
        $this->roleSelection = UserRole::TENANT_USER->value;
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'users' => $this->tenantUserQuery()
                ->with('customRole')
                ->orderBy('name')
                ->paginate($this->perPage),
            'tenantName' => auth()->user()->tenant?->name ?? 'Organisasi',
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Administration</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Tenant Users</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola pengguna {{ $tenantName }}.</p>
        </div>
        <button type="button" wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Add User</button>
    </div>

    @if ($showForm)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ $editingUserId ? 'Edit User' : 'Add User' }}</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-slate-700">Name</label>
                    <input wire:model="name" type="text" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">NIP</label>
                    <input wire:model="nip" type="text" maxlength="32" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                    @error('nip')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Email / Login</label>
                    <input wire:model="email" type="email" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Role</label>
                    <select wire:model.live="roleSelection" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm">
                        <option value="tenant_user">Tenant User</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Role efektif ditentukan oleh RBAC. Tenant Admin hanya mengelola Tenant User dari halaman ini.</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Password {{ $editingUserId ? '(optional)' : '' }}</label>
                    <input wire:model="password" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                    @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Status</label>
                    <select wire:model="status" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm">
                        @foreach(UserStatus::cases() as $userStatus)
                            <option value="{{ $userStatus->value }}">{{ ucfirst($userStatus->value) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                <button type="button" wire:click="save" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Save User</button>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">NIP</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $user->email }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">{{ $user->nip ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-700">{{ $user->effectiveRole()?->name ?? 'Tenant User' }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === UserStatus::ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $user->status->value }}</span>
                            </td>
                            <td class="px-6 py-4 text-right"><x-ui.user-actions :user="$user" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada Tenant User.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between border-t border-slate-100 px-6 py-3 text-sm text-slate-500">
            <span>Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users</span>
            <div>{{ $users->links() }}</div>
        </div>
    </div>

    @if($showSignerPin)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:click.self="closeSignerPin">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-slate-900">PIN Tanda Tangan</h2>
                <p class="mt-1 text-sm text-slate-500">Atur PIN tanda tangan untuk {{ $signerPinUserName }}.</p>
                <div class="mt-5 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">PIN</label>
                        <input wire:model="signerPin" type="password" inputmode="numeric" maxlength="6" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                        @error('signerPin')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Konfirmasi PIN</label>
                        <input wire:model="signerPinConfirmation" type="password" inputmode="numeric" maxlength="6" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                        @error('signerPinConfirmation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="closeSignerPin" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                    <button type="button" wire:click="saveSignerPin" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Save PIN</button>
                </div>
            </div>
        </div>
    @endif
</div>
