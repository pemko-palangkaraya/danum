<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LetterType;
use App\Models\User;

class LetterTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, LetterType $letterType): bool
    {
        return $user->isSuperAdmin() && $letterType->isGlobal();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, LetterType $letterType): bool
    {
        return $user->isSuperAdmin() && $letterType->isGlobal();
    }

    public function delete(User $user, LetterType $letterType): bool
    {
        return $user->isSuperAdmin() && $letterType->isGlobal();
    }

    public function restore(User $user, LetterType $letterType): bool
    {
        return $user->isSuperAdmin() && $letterType->isGlobal();
    }

    public function forceDelete(User $user, LetterType $letterType): bool
    {
        return false;
    }
}
