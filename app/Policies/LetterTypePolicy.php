<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LetterType;
use App\Models\User;

class LetterTypePolicy
{
    /**
     * Determine whether the user can view any letter types.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the letter type.
     */
    public function view(User $user, LetterType $letterType): bool
    {
        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id === $letterType->tenant_id;
    }

    /**
     * Determine whether the user can create letter types.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the letter type.
     */
    public function update(User $user, LetterType $letterType): bool
    {
        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id === $letterType->tenant_id;
    }

    /**
     * Determine whether the user can delete the letter type.
     */
    public function delete(User $user, LetterType $letterType): bool
    {
        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id === $letterType->tenant_id;
    }

    /**
     * Determine whether the user can restore the letter type.
     */
    public function restore(User $user, LetterType $letterType): bool
    {
        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id === $letterType->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the letter type.
     */
    public function forceDelete(User $user, LetterType $letterType): bool
    {
        return false;
    }
}
