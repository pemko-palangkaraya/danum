<?php

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public bool $showForm = false;
    public ?int $editingRoleId = null;
    public string $name = '';
    public array $selectedPermissions = [];
    public bool $editingSystemRole = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission(PermissionEnum::RBAC_VIEW), 403);
    }
    public function canManage(): bool
    {
        return auth()->user()?->isSuperAdmin() || auth()->user()?->isTenantAdmin();
    }

    public function roles()
    {
        $query = Role::query()->with('permissions')->orderByDesc('is_system')->orderBy('name');
        if (! auth()->user()?->isSuperAdmin()) {
            $query->where(fn($q) => $q->where('is_system', true)->where('slug', UserRole::TENANT_ADMIN->value)
                ->orWhere(fn($custom) => $custom->where('is_system', false)->where('scope', 'tenant')->where('tenant_id', auth()->user()?->tenant_id)));
        }
        return $query->get();
    }

    public function permissions()
    {
        return Permission::query()->whereIn('slug', array_map(fn(PermissionEnum $permission) => $permission->value, PermissionEnum::forCustomRole()))->orderBy('module')->orderBy('action')->get();
    }
    public function groupedPermissions(): array
    {
        return $this->permissions()->groupBy('module')->all();
    }
    public function label(Permission $permission): string
    {
        return str($permission->action)->replace(['.', '-'], ' ')->title()->toString();
    }
    public function openCreate(): void
    {
        abort_unless($this->canManage(), 403);
        $this->resetForm();
        $this->showForm = true;
    }
    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin() || ($this->canManage() && ! $this->isSystemRole($id)), 403);

        $role = $this->editableRole($id);
        $this->editingRoleId = $role->id;
        $this->editingSystemRole = $role->is_system;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->intersect($this->permissions()->pluck('id')->map(fn($id) => (string) $id))
            ->values()
            ->all();
        $this->showForm = true;
    }

    public function save(AuditLogService $auditLogService): void
    {
        abort_unless($this->canManage(), 403);

        Validator::make(
            ['name' => $this->name, 'permissions' => $this->selectedPermissions],
            [
                'name' => ['required', 'string', 'max:100'],
                'permissions' => ['array'],
                'permissions.*' => ['integer', 'exists:permissions,id'],
            ],
        )->validate();

        $actor = auth()->user();
        $role = $this->editingRoleId ? $this->editableRole($this->editingRoleId) : new Role();
        $wasCreated = $role->exists === false;
        $oldPermissions = $role->exists
            ? $role->permissions()->pluck('slug')->sort()->values()->all()
            : [];

        if ($wasCreated) {
            $role->tenant_id = $actor->isSuperAdmin() ? null : $actor->tenant_id;
            $role->scope = $actor->isSuperAdmin() ? 'global' : 'tenant';
            $role->is_system = false;
            $role->is_active = true;
            $role->slug = $this->uniqueSlug($this->name);
        }

        if (! $role->is_system) {
            $role->name = $this->name;
        }

        $role->save();

        $allowed = collect($this->assignablePermissionIds());
        $requested = collect($this->selectedPermissions)
            ->map(fn($id) => (int) $id)
            ->intersect($allowed)
            ->values()
            ->all();

        $role->permissions()->sync($requested);

        $newPermissions = $role->permissions()->pluck('slug')->sort()->values()->all();

        $auditLogService->record(
            action: $wasCreated ? 'rbac.role.created' : 'rbac.role.updated',
            user: $actor,
            auditable: $role,
            oldValues: $wasCreated ? null : [
                'permissions' => $oldPermissions,
            ],
            newValues: [
                'name' => $role->name,
                'slug' => $role->slug,
                'scope' => $role->scope,
                'permissions' => $newPermissions,
            ],
            tenantId: $role->tenant_id,
        );

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Perubahan role berhasil disimpan.');
    }
    public function toggleActive(int $id, AuditLogService $auditLogService): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $role = $this->editableRole($id);
        $old = $role->is_active;
        $role->update(['is_active' => !$old]);
        $auditLogService->record(action: 'rbac.role.status_updated', user: auth()->user(), auditable: $role, oldValues: ['is_active' => $old], newValues: ['is_active' => $role->is_active], tenantId: $role->tenant_id);
    }
    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingRoleId = null;
        $this->editingSystemRole = false;
        $this->name = '';
        $this->selectedPermissions = [];
        $this->resetValidation();
    }
    private function editableRole(int $id): Role
    {
        $query = Role::query()->whereKey($id);

        if (auth()->user()?->isSuperAdmin()) {
            $role = $query->firstOrFail();

            abort_if($role->slug === UserRole::SUPER_ADMIN->value, 403);

            return $role;
        }

        return $query
            ->where('is_system', false)
            ->where('scope', 'tenant')
            ->where('tenant_id', auth()->user()?->tenant_id)
            ->firstOrFail();
    }

    private function isSystemRole(int $id): bool
    {
        return Role::query()->whereKey($id)->where('is_system', true)->exists();
    }
    private function assignablePermissionIds(): array
    {
        return $this->permissions()->pluck('id')->all();
    }
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'custom-role';
        $slug = $base;
        $counter = 2;
        while (Role::query()->where('slug', $slug)->exists()) $slug = $base . '-' . $counter++;
        return $slug;
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Administration</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Role &amp; Access Control</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Kelola system role dan custom role sesuai kewenangan.</p>
        </div>@if($this->canManage())<button type="button" wire:click="openCreate" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">+ Create Custom Role</button>@endif
    </div>
    <div class="space-y-4">@foreach($this->roles() as $role)<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-semibold text-slate-900">{{ $role->name }}</h2><span class="rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-700">{{ $role->is_system?'SYSTEM':'CUSTOM' }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ $role->scope==='global'?'Global / seluruh tenant':'Tenant scoped' }} · {{ $role->slug }}</p>
                </div>@if($this->canManage() && (!$role->is_system && auth()->user()->isTenantAdmin() || auth()->user()->isSuperAdmin() && $role->slug !== UserRole::SUPER_ADMIN->value))<div class="flex gap-2"><button type="button" wire:click="openEdit({{ $role->id }})" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">{{ $role->is_system ? 'Edit Permissions' : 'Edit Permissions' }}</button>@if(auth()->user()->isSuperAdmin() && !$role->is_system)<button type="button" wire:click="toggleActive({{ $role->id }})" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">{{ $role->is_active?'Deactivate':'Activate' }}</button>@endif</div>@endif
            </div>
            <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-3">@foreach($role->permissions as $permission)<div class="bg-white px-5 py-3">
                    <p class="text-sm font-medium text-slate-800">{{ $permission->name }}</p>
                    <p class="text-[11px] text-slate-400">{{ $permission->slug }}</p>
                </div>@endforeach@if($role->permissions->isEmpty())<div class="bg-white px-5 py-4 text-sm text-slate-500 sm:col-span-2 lg:col-span-3">Tidak ada permission aktif.</div>@endif</div>
        </section>@endforeach</div>
    @if($showForm)<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" wire:keydown.escape="resetForm">
        <div class="absolute inset-0" wire:click="resetForm"></div>
        <section class="relative z-10 max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true">
            <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $editingRoleId ? ($editingSystemRole ? 'Edit System Role' : 'Edit Custom Role') : 'Create Custom Role' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $editingSystemRole ? 'Atur permission system role. Nama dan identitas role tetap dikunci.' : 'Pilih permission yang boleh diwariskan sesuai kewenangan actor.' }}</p>
                </div><button type="button" wire:click="resetForm" wire:loading.attr="disabled" aria-label="Tutup" title="Tutup" class="flex h-9 w-9 items-center justify-center rounded-lg text-2xl leading-none text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-300 disabled:opacity-50">&times;</button>
            </div>
            <div class="max-h-[calc(90vh-145px)] overflow-y-auto px-5 py-5 sm:px-6">
                <div><label class="text-sm font-medium text-slate-700">Role Name</label><input wire:model="name" type="text" maxlength="100" {{ $editingSystemRole ? 'readonly' : '' }} autofocus class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm {{ $editingSystemRole ? 'bg-slate-50 text-slate-500' : '' }}">@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="mt-6 space-y-4">@foreach($this->groupedPermissions() as $module=>$permissions)<div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">{{ str($module)->replace('-',' ')->title() }}</div>
                        <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-3">@foreach($permissions as $permission)<label class="flex cursor-pointer items-center gap-3 bg-white px-4 py-3 hover:bg-slate-50"><input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->id }}" class="rounded border-slate-300"><span><span class="block text-sm font-medium text-slate-800">{{ $this->label($permission) }}</span><span class="block text-[11px] text-slate-400">{{ $permission->slug }}</span></span></label>@endforeach</div>
                    </div>@endforeach</div>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6"><button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button><button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"><span wire:loading.remove wire:target="save">Save Role</span><span wire:loading wire:target="save">Menyimpan...</span></button></div>
        </section>
    </div>@endif
</div>