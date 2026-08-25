<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->role === UserRole::SUPER_ADMIN) {
            return true;
        }

        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id === $tenant->id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Tenant users may view their own organization's profile.
     * Tenant admin is intentionally included via the tenant_user role family.
     */
    public function viewProfile(User $user, Tenant $tenant): bool
    {
        return in_array($user->role, [UserRole::TENANT_ADMIN, UserRole::TENANT_USER], true)
            && $user->tenant_id === $tenant->id;
    }

    public function updateProfile(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
