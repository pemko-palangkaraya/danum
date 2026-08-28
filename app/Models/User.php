<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission;
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

    public function hasPermission(Permission|string $permission): bool
    {
        if ($this->status !== UserStatus::ACTIVE) {
            return false;
        }

        $permission = $permission instanceof Permission
            ? $permission
            : Permission::tryFrom($permission);

        return $permission !== null
            && in_array($permission, Permission::forRole($this->role), true);
    }

    /** @param list<Permission|string> $permissions */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<Permission|string> $permissions */
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
        return $this->hasPermission(Permission::POSITIONS_MANAGE);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
