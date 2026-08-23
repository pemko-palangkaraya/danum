<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    /**
     * Determine whether the user can view any tenants.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can view the tenant.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->role === UserRole::SUPER_ADMIN) {
            return true;
        }

        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id === $tenant->id;
    }

    /**
     * Determine whether the user can create tenants.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can update the tenant.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can delete the tenant.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can restore the tenant.
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the tenant.
     */
    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can view the tenant profile.
     */
    public function viewProfile(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id === $tenant->id;
    }

    /**
     * Determine whether the user can update the tenant profile.
     */
    public function updateProfile(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
