<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::USERS_VIEW);
    }

    public function view(User $user, User $targetUser): bool
    {
        return $user->hasPermission(Permission::USERS_VIEW)
            && ($user->isSuperAdmin() || $user->tenant_id === $targetUser->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::USERS_CREATE);
    }

    public function update(User $user, User $targetUser): bool
    {
        if (! $user->hasPermission(Permission::USERS_UPDATE)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $targetUser->tenant_id
            && ($targetUser->id === $user->id || $targetUser->effectiveRole()?->slug === 'tenant_user');
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->hasPermission(Permission::USERS_DELETE)
            && ($user->isSuperAdmin() || $user->tenant_id === $targetUser->tenant_id);
    }

    public function forceDelete(User $user, User $targetUser): bool
    {
        return $this->delete($user, $targetUser);
    }
}
