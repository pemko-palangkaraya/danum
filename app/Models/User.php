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
        'signing_pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'signing_pin_set_at' => 'datetime',
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

    public function canManagePositions(): bool
    {
        return $this->isSuperAdmin() || $this->isTenantAdmin();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
