<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use App\Enums\PlatformRole;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = ['name', 'nip', 'email', 'password', 'platform_role', 'custom_role_id', 'status', 'tenant_id'];
    protected $hidden = ['signing_pin_hash', 'signing_pin_failed_attempts', 'signing_pin_locked_until'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'platform_role' => PlatformRole::class,
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'signing_pin_set_at' => 'datetime',
            'signing_pin_failed_attempts' => 'integer',
            'signing_pin_locked_until' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return ($this->platform_role === PlatformRole::SUPER_ADMIN || $this->role === UserRole::SUPER_ADMIN)
            && $this->tenant_id === null
            && $this->custom_role_id === null;
    }

    public function isTenantMember(): bool { return $this->tenant_id !== null && ! $this->isSuperAdmin(); }
    public function isTenantUser(): bool { return $this->isTenantMember(); }
    public function isTenantAdmin(): bool { return $this->isTenantMember() && $this->roleModel()?->slug === 'tenant_admin'; }
    public function customRole(): BelongsTo { return $this->belongsTo(Role::class, 'custom_role_id'); }

    public function roleModel(): ?Role
    {
        if ($this->isSuperAdmin() || $this->tenant_id === null) return null;

        if ($this->custom_role_id !== null) {
            return Role::query()
                ->whereKey($this->custom_role_id)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where(fn ($q) => $q->where('scope', 'global')->whereNull('tenant_id'))
                        ->orWhere(fn ($q) => $q->where('scope', 'tenant')->where('tenant_id', $this->tenant_id))
                        ->orWhere(fn ($q) => $q->where('scope', 'tenant')->whereNull('tenant_id')->where('is_system', true));
                })
                ->first();
        }

        $legacySlug = $this->role?->value;

        if (! in_array($legacySlug, [UserRole::TENANT_ADMIN->value, UserRole::TENANT_USER->value], true)) {
            return null;
        }

        return Role::query()
            ->where('slug', $legacySlug)
            ->where('is_system', true)
            ->where('is_active', true)
            ->where(function ($query) use ($legacySlug) {
                $query->where(fn ($q) => $q->where('scope', 'global')->whereNull('tenant_id'))
                    ->orWhere(fn ($q) => $q->where('scope', 'tenant')->where('tenant_id', $this->tenant_id))
                    ->orWhere(fn ($q) => $q->where('scope', 'tenant')->whereNull('tenant_id')->where('is_system', true));
            })
            ->first();
    }

    /**
     * Returns the role that may be displayed in tenant-facing UI.
     * Global custom roles remain effective for authorization via roleModel(),
     * but their names must not be exposed to tenant administrators.
     */
    public function effectiveRole(): ?Role
    {
        $role = $this->roleModel();

        if ($role?->scope !== 'tenant') {
            return null;
        }

        return $role;
    }

    public function hasPermission(PermissionEnum|string $permission): bool
    {
        if ($this->status !== UserStatus::ACTIVE) return false;
        if ($this->isSuperAdmin()) return true;
        if (! $this->isTenantMember()) return false;

        $slug = $permission instanceof PermissionEnum ? $permission->value : $permission;
        $role = $this->roleModel();

        return $role !== null
            && $role->permissions()->where('slug', $slug)->exists();
    }

    public function hasAnyPermission(array $permissions): bool { foreach ($permissions as $permission) if ($this->hasPermission($permission)) return true; return false; }
    public function hasAllPermissions(array $permissions): bool { foreach ($permissions as $permission) if (! $this->hasPermission($permission)) return false; return true; }
    public function canManagePositions(): bool { return $this->hasPermission(PermissionEnum::POSITIONS_MANAGE); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
}
