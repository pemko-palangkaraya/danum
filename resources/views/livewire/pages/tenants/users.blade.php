<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $tenantId = '';
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public ?int $editingUserId = null;
    public bool $showForm = false;

    public function mount(): void
    {
        $this->tenantId = (string) request()->route('tenant');
        abort_unless(Tenant::query()->find($this->tenantId), 404);
        $this->authorize('viewAny', User::class);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::query()->where('tenant_id', $this->tenantId)->findOrFail($id);
        $this->authorize('update', $user);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->showForm = true;
    }

    public function save(UserService $userService): void
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => UserRole::TENANT_USER->value,
            'tenant_id' => $this->tenantId,
        ];

        if ($this->editingUserId) {
            $user = User::query()->where('tenant_id', $this->tenantId)->findOrFail($this->editingUserId);
            $this->authorize('update', $user);
            if ($this->password === '') unset($data['password']);
            $rules = (new UpdateUserRequest())->rules();
            $validated = Validator::make($data, $rules)->validate();
            if (isset($validated['password'])) $validated['password'] = Hash::make($validated['password']);
            $userService->update($user, $validated);
        } else {
            $this->authorize('create', User::class);
            $validated = Validator::make($data, (new StoreUserRequest())->rules())->validate();
            $validated['password'] = Hash::make($validated['password']);
            $userService->create($validated);
        }

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'User berhasil disimpan.');
    }

    public function toggleStatus(int $id, UserService $userService): void
    {
        $user = User::query()->where('tenant_id', $this->tenantId)->findOrFail($id);
        $this->authorize('update', $user);
        $userService->update($user, [
            'status' => $user->status === UserStatus::ACTIVE ? UserStatus::INACTIVE : UserStatus::ACTIVE,
        ]);
        $this->dispatch('toast', type: 'success', message: 'Status user diperbarui.');
    }

    public function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->showForm = false;
    }

    public function with(): array
    {
        $tenant = Tenant::query()->findOrFail($this->tenantId);
        return ['tenant' => $tenant, 'users' => User::query()->where('tenant_id', $tenant->id)->orderBy('name')->get()];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('tenants.show', $tenantId) }}" class="text-sm text-slate-500 hover:text-slate-700">← {{ $tenant->name }}</a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Tenant Users</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola administrator dan pengguna {{ $tenant->name }}.</p>
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
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Email</label>
                    <input wire:model="email" type="email" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-slate-700">Password {{ $editingUserId ? '(kosongkan jika tidak diubah)' : '' }}</label>
                    <input wire:model="password" type="password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
                <thead class="bg-slate-50"><tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">User</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Action</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-6 py-4"><div class="font-medium text-slate-900">{{ $user->name }}</div><div class="text-xs text-slate-500">{{ $user->email }}</div></td>
                            <td class="px-6 py-4 text-sm text-slate-700">{{ $user->role->value }}</td>
                            <td class="px-6 py-4 text-sm"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === UserStatus::ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $user->status->value }}</span></td>
                            <td class="px-6 py-4 text-right"><x-ui.user-actions :user="$user" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada user tenant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
