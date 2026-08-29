<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\LetterType;
use App\Models\User;

class LetterTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::LETTER_TYPES_VIEW);
    }

    public function view(User $user, LetterType $letterType): bool
    {
        return $user->hasPermission(Permission::LETTER_TYPES_VIEW) && $letterType->isGlobal();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::LETTER_TYPES_MANAGE);
    }

    public function update(User $user, LetterType $letterType): bool
    {
        return $user->hasPermission(Permission::LETTER_TYPES_MANAGE) && $letterType->isGlobal();
    }

    public function delete(User $user, LetterType $letterType): bool
    {
        return $user->hasPermission(Permission::LETTER_TYPES_MANAGE) && $letterType->isGlobal();
    }

    public function restore(User $user, LetterType $letterType): bool
    {
        return $user->hasPermission(Permission::LETTER_TYPES_MANAGE) && $letterType->isGlobal();
    }

    public function forceDelete(User $user, LetterType $letterType): bool
    {
        return false;
    }
}
