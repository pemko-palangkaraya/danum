<?php

use App\Enums\UserStatus;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SignerPinService;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public int $perPage = 10;
    public string $tenantId = '';
    public string $name = '';
    public string $nip = '';
    public string $email = '';
    public string $password = '';
    public ?int $editingUserId = null;
    public bool $showForm = false;
    public bool $showSignerPin = false;
    public ?int $signerPinUserId = null;
    public string $signerPinUserName = '';
    public string $signerPin = '';
    public string $signerPinConfirmation = '';

    public function updatedPerPage(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->tenantId = (string) request()->route('tenant');
        abort_unless(Tenant::query()->find($this->tenantId), 404);
        $this->authorize('viewAny', User::class);
    }

    public function create(): void { $this->resetForm(); $this->showForm = true; }

    public function edit(int $id): void
    {
        $user = User::query()->where('tenant_id', $this->tenantId)->findOrFail($id);
        $this->authorize('update', $user);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->nip = (string) ($user->nip ?? '');
        $this->email = $user->email;
        $this->password = '';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openSignerPin(int $id): void
    {
        $user = User::query()->where('tenant_id', $this->tenantId)->findOrFail($id);
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
        $user = User::query()->where('tenant_id', $this->tenantId)->findOrFail($this->signerPinUserId);
        $this->authorize('update', $user);
        $validated = Validator::make([
            'signerPin' => $this->signerPin,
            'signerPinConfirmation' => $this->signerPinConfirmation,
        ], [
            'signerPin' => ['required', 'digits:6'],
            'signerPinConfirmation' => ['required', 'same:signerPin'],
        ], [
            'signerPin.required' => 'PIN wajib diisi.',
            'signerPin.digits' => 'PIN harus terdiri dari 6 digit.',
            'signerPinConfirmation.required' => 'Konfirmasi PIN wajib diisi.',
            'signerPinConfirmation.same' => 'Konfirmasi PIN tidak sama.',
        ])->validate();
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
        $data = [
            'name' => $this->name,
            'nip' => $this->nip,
            'email' => $this->email,
            'password' => $this->password,
            'role' => 'tenant_user',
            'tenant_id' => $this->tenantId,
            'status' => UserStatus::ACTIVE->value,
        ];

        if ($this->editingUserId) {
            $user = User::query()->where('tenant_id', $this->tenantId)->findOrFail($this->editingUserId);
            $this->authorize('update', $user);
            if ($this->password === '') unset($data['password']);
            $rules = UpdateUserRequest::rulesFor($user);
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())];
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
        $userService->update($user, ['status' => $user->status === UserStatus::ACTIVE ? UserStatus::INACTIVE : UserStatus::ACTIVE]);
        $this->dispatch('toast', type: 'success', message: 'Status user diperbarui.');
    }

    public function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->nip = '';
        $this->email = '';
        $this->password = '';
        $this->showForm = false;
        $this->resetValidation();
    }

    public function with(): array
    {
        $tenant = Tenant::query()->findOrFail($this->tenantId);
        return ['tenant' => $tenant, 'users' => User::query()->with('customRole')->where('tenant_id', $tenant->id)->orderBy('name')->paginate($this->perPage)];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4"><div><a href="{{ route('tenants.show', $tenantId) }}" class="text-sm text-slate-500 hover:text-slate-700">← {{ $tenant->name }}</a><h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Tenant Users</h1><p class="mt-1 text-sm text-slate-500">Kelola administrator dan pengguna {{ $tenant->name }}.</p></div><button type="button" wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Add User</button></div>

    @if ($showForm)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="text-sm font-semibold text-slate-900">{{ $editingUserId ? 'Edit User' : 'Add User' }}</h2><div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div><label class="text-sm font-medium text-slate-700">Name</label><input wire:model="name" type="text" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-medium text-slate-700">NIP</label><input wire:model="nip" type="text" maxlength="32" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm" placeholder="Nomor Induk Pegawai">@error('nip')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-medium text-slate-700">Email</label><input wire:model="email" type="email" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div class="sm:col-span-2"><label class="text-sm font-medium text-slate-700">Password {{ $editingUserId ? '(kosongkan jika tidak diubah)' : '' }}</label><input wire:model="password" type="password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        </div><div class="mt-5 flex justify-end gap-3"><button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button><button type="button" wire:click="save" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Save User</button></div></div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">User</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">NIP</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Role</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse ($users as $user)<tr><td class="px-6 py-4"><div class="font-medium text-slate-900">{{ $user->name }}</div><div class="text-xs text-slate-500">{{ $user->email }}</div></td><td class="px-6 py-4 text-sm text-slate-700">{{ $user->nip ?: '-' }}</td><td class="px-6 py-4 text-sm text-slate-700">{{ $user->customRole?->name ?? $user->roleModel()?->name ?? '-' }}</td><td class="px-6 py-4 text-sm"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === UserStatus::ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $user->status->value }}</span></td><td class="px-6 py-4 text-right"><x-ui.user-actions :user="$user" /></td></tr>
        @empty<tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada user tenant.</td></tr>@endforelse
        </tbody></table></div><div class="border-t border-slate-100 p-4">{{ $users->onEachSide(1)->links() }}</div></div>

    @if($showSignerPin)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="closeSignerPin">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold text-slate-900">PIN Tanda Tangan</h2><p class="mt-1 text-sm text-slate-500">Credential signing untuk {{ $signerPinUserName }}.</p></div><button type="button" wire:click="closeSignerPin" class="rounded-lg px-2 py-1 text-slate-400 hover:bg-slate-100">✕</button></div>
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">PIN ini berbeda dari password login. PIN hanya digunakan untuk mengotorisasi tindakan tanda tangan elektronik dan tidak dapat dilihat kembali setelah disimpan.</div>
                <div class="mt-5 space-y-4"><div><label class="text-sm font-medium text-slate-700">PIN baru</label><input wire:model="signerPin" type="password" inputmode="numeric" maxlength="6" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm tracking-[0.4em]" placeholder="••••••">@error('signerPin')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="text-sm font-medium text-slate-700">Konfirmasi PIN</label><input wire:model="signerPinConfirmation" type="password" inputmode="numeric" maxlength="6" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm tracking-[0.4em]" placeholder="••••••">@error('signerPinConfirmation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div>
                <div class="mt-6 flex justify-end gap-2"><button type="button" wire:click="closeSignerPin" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button type="button" wire:click="saveSignerPin" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Simpan PIN</button></div>
            </div>
        </div>
    @endif
</div>
