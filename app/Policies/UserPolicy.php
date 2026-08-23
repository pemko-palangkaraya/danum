<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN || $user->isTenantAdmin();
    }

    public function view(User $user, User $targetUser): bool
    {
        if ($user->role === UserRole::SUPER_ADMIN) {
            return true;
        }

        return $user->isTenantAdmin()
            && $user->tenant_id === $targetUser->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN || $user->isTenantAdmin();
    }

    public function update(User $user, User $targetUser): bool
    {
        if ($user->role === UserRole::SUPER_ADMIN) {
            return true;
        }

        return $user->isTenantAdmin()
            && $user->tenant_id === $targetUser->tenant_id
            && $targetUser->role === UserRole::TENANT_USER;
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function forceDelete(User $user, User $targetUser): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
