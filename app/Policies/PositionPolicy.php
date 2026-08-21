<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isActive($user);
    }

    public function view(User $user, Position $position): bool
    {
        return $this->canAccessPosition($user, $position);
    }

    public function create(User $user): bool
    {
        return $this->isActive($user);
    }

    public function update(User $user, Position $position): bool
    {
        return $this->canAccessPosition($user, $position);
    }

    public function delete(User $user, Position $position): bool
    {
        return $this->canAccessPosition($user, $position);
    }

    public function restore(User $user, Position $position): bool
    {
        return $this->canAccessPosition($user, $position);
    }

    private function isActive(User $user): bool
    {
        return $user->status === UserStatus::ACTIVE;
    }

    private function canAccessPosition(
        User $user,
        Position $position
    ): bool {
        if (!$this->isActive($user)) {
            return false;
        }

        if ($user->role === UserRole::SUPER_ADMIN) {
            return true;
        }

        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id === $position->tenant_id;
    }
}
