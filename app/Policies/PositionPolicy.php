<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission(Permission::POSITIONS_VIEW); }
    public function view(User $user, Position $position): bool { return $this->canAccessPosition($user, $position); }
    public function create(User $user): bool { return $user->hasPermission(Permission::POSITIONS_MANAGE); }
    public function update(User $user, Position $position): bool { return $user->hasPermission(Permission::POSITIONS_MANAGE) && $this->canAccessPosition($user, $position); }
    public function delete(User $user, Position $position): bool { return $user->hasPermission(Permission::POSITIONS_MANAGE) && $this->canAccessPosition($user, $position); }
    public function restore(User $user, Position $position): bool { return $user->hasPermission(Permission::POSITIONS_MANAGE) && ($user->isSuperAdmin() || $user->tenant_id === $position->tenant_id); }
    public function manageHolder(User $user, Position $position): bool { return $user->hasPermission(Permission::POSITIONS_MANAGE) && $this->canAccessPosition($user, $position); }

    private function canAccessPosition(User $user, Position $position): bool
    {
        if (! $user->hasPermission(Permission::POSITIONS_VIEW)) return false;
        return $user->isSuperAdmin() || $user->tenant_id === $position->tenant_id;
    }
}
