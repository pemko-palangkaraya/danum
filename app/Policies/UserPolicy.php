<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $targetUser): bool
    {
        if ($user->role === UserRole::SUPER_ADMIN) {
            return true;
        }

        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id !== null
            && $user->tenant_id === $targetUser->tenant_id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $targetUser): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the user.
     */
    public function forceDelete(User $user, User $targetUser): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}