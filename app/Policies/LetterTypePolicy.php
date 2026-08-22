<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LetterType;
use App\Models\User;

class LetterTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function view(User $user, LetterType $letterType): bool
    {
        return $user->role === UserRole::SUPER_ADMIN
            || ($user->role === UserRole::TENANT_USER && $user->tenant_id !== null && $letterType->tenant_id === null);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function update(User $user, LetterType $letterType): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function delete(User $user, LetterType $letterType): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function restore(User $user, LetterType $letterType): bool
    {
        return $user->role === UserRole::SUPER_ADMIN;
    }

    public function forceDelete(User $user, LetterType $letterType): bool
    {
        return false;
    }
}
