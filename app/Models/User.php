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
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name', 'nip', 'email', 'password', 'role', 'status', 'tenant_id',
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

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    public function isTenantAdmin(): bool
    {
        return $this->role === UserRole::TENANT_ADMIN && $this->tenant_id !== null;
    }

    public function isTenantUser(): bool
    {
        return in_array($this->role, [UserRole::TENANT_ADMIN, UserRole::TENANT_USER], true)
            && $this->tenant_id !== null;
    }

    public function roleModel(): ?Role
    {
        return Role::query()
            ->where('slug', $this->role->value)
            ->where('is_active', true)
            ->first();
    }

    public function hasPermission(PermissionEnum|string $permission): bool
    {
        if ($this->status !== UserStatus::ACTIVE) {
            return false;
        }

        $slug = $permission instanceof PermissionEnum ? $permission->value : $permission;

        return Permission::query()
            ->where('slug', $slug)
            ->whereHas('roles', fn ($query) => $query
                ->where('slug', $this->role->value)
                ->where('is_active', true))
            ->exists();
    }

    /** @param list<PermissionEnum|string> $permissions */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<PermissionEnum|string> $permissions */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    public function canManagePositions(): bool
    {
        return $this->hasPermission(PermissionEnum::POSITIONS_MANAGE);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
