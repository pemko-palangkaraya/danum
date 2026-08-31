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

        if (!auth()->user()?->isSuperAdmin()) {
            $query->where(fn($q) => $q->where('is_system', true)->where('slug', UserRole::TENANT_ADMIN->value)
                ->orWhere(fn($custom) => $custom->where('is_system', false)->where('scope', 'tenant')->where('tenant_id', auth()->user()?->tenant_id)));
        }

        return $query->get();
    }

    public function permissions()
    {
        return Permission::query()
            ->whereIn('slug', array_map(fn(PermissionEnum $permission) => $permission->value, PermissionEnum::forCustomRole()))
            ->orderBy('module')
            ->orderBy('action')
            ->get();
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
        abort_unless(auth()->user()?->isSuperAdmin() || ($this->canManage() && !$this->isSystemRole($id)), 403);

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

        if (!$role->is_system) {
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
            oldValues: $wasCreated ? null : ['permissions' => $oldPermissions],
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

        $auditLogService->record(
            action: 'rbac.role.status_updated',
            user: auth()->user(),
            auditable: $role,
            oldValues: ['is_active' => $old],
            newValues: ['is_active' => $role->is_active],
            tenantId: $role->tenant_id,
        );
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

        while (Role::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
};
?>

<div class="space-y-6">
    @include('livewire.pages.rbac._header')
    @include('livewire.pages.rbac._roles')
    @include('livewire.pages.rbac._form')
</div>
