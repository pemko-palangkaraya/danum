<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            ? $user->hasPermission(Permission::USERS_VIEW)
            : $user->hasPermission(Permission::TENANT_USERS_VIEW);
    }

    public function view(User $user, User $targetUser): bool
    {
        return $this->viewAny($user)
            && ($user->isSuperAdmin() || $user->tenant_id === $targetUser->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            ? $user->hasPermission(Permission::USERS_CREATE)
            : $user->hasPermission(Permission::TENANT_USERS_VIEW);
    }

    public function update(User $user, User $targetUser): bool
    {
        if (! $user->isSuperAdmin() && ! $user->hasPermission(Permission::TENANT_USERS_VIEW)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return $user->hasPermission(Permission::USERS_UPDATE);
        }

        return $user->tenant_id === $targetUser->tenant_id
            && ($targetUser->id === $user->id || $targetUser->effectiveRole()?->slug === 'tenant_user');
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->isSuperAdmin()
            ? $user->hasPermission(Permission::USERS_DELETE)
            : false;
    }

    public function forceDelete(User $user, User $targetUser): bool
    {
        return $this->delete($user, $targetUser);
    }
}
