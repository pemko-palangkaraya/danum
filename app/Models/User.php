<?php

declare(strict_types=1);

namespace App\Models;

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
        'name',
        'nip',
        'email',
        'password',
        'role',
        'status',
        'tenant_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Determine whether this user is a super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether this user is a tenant administrator.
     */
    public function isTenantAdmin(): bool
    {
        return $this->role === UserRole::TENANT_ADMIN && $this->tenant_id !== null;
    }

    /**
     * Determine whether this user is a tenant-scoped user.
     */
    public function isTenantUser(): bool
    {
        return in_array($this->role, [UserRole::TENANT_ADMIN, UserRole::TENANT_USER], true)
            && $this->tenant_id !== null;
    }

    /**
     * Determine whether this user may manage tenant positions/holders.
     */
    public function canManagePositions(): bool
    {
        return $this->isSuperAdmin() || $this->isTenantAdmin();
    }

    /**
     * Get the tenant associated with the user.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
