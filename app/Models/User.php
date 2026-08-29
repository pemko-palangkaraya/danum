<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
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

    protected $fillable = [
        'name', 'nip', 'email', 'password', 'role', 'custom_role_id', 'status', 'tenant_id',
    ];

    protected $hidden = [
        'signing_pin_hash', 'signing_pin_failed_attempts', 'signing_pin_locked_until',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'signing_pin_set_at' => 'datetime',
            'signing_pin_failed_attempts' => 'integer',
            'signing_pin_locked_until' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool { return $this->role === UserRole::SUPER_ADMIN && $this->custom_role_id === null; }
    public function isTenantAdmin(): bool { return $this->role === UserRole::TENANT_ADMIN && $this->custom_role_id === null && $this->tenant_id !== null; }
    public function isTenantUser(): bool { return $this->tenant_id !== null && ! $this->isSuperAdmin(); }
    public function customRole(): BelongsTo { return $this->belongsTo(Role::class, 'custom_role_id'); }

    public function roleModel(): ?Role
    {
        if ($this->custom_role_id !== null) {
            if ($this->tenant_id === null) return null;
            return $this->customRole()
                ->where('is_system', false)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('scope', 'global')
                        ->orWhere(fn ($tenant) => $tenant->where('scope', 'tenant')->where('tenant_id', $this->tenant_id));
                })
                ->first();
        }
        return Role::query()->where('slug', $this->role->value)->where('is_active', true)->first();
    }

    public function effectiveRole(): ?Role { return $this->roleModel(); }

    public function hasPermission(PermissionEnum|string $permission): bool
    {
        if ($this->status !== UserStatus::ACTIVE) return false;
        if ($this->isSuperAdmin()) return true;
        $slug = $permission instanceof PermissionEnum ? $permission->value : $permission;
        $role = $this->roleModel();
        return $role !== null && $role->permissions()->where('slug', $slug)->exists();
    }

    /** @param list<PermissionEnum|string> $permissions */
    public function hasAnyPermission(array $permissions): bool { foreach ($permissions as $permission) if ($this->hasPermission($permission)) return true; return false; }
    /** @param list<PermissionEnum|string> $permissions */
    public function hasAllPermissions(array $permissions): bool { foreach ($permissions as $permission) if (! $this->hasPermission($permission)) return false; return true; }
    public function canManagePositions(): bool { return $this->hasPermission(PermissionEnum::POSITIONS_MANAGE); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
}
