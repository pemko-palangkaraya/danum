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
        return $user->role === UserRole::TENANT_USER && $user->tenant_id !== null;
    }

    public function view(User $user, LetterType $letterType): bool
    {
        return $this->belongsToTenant($user, $letterType);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, LetterType $letterType): bool
    {
        return $this->belongsToTenant($user, $letterType);
    }

    public function delete(User $user, LetterType $letterType): bool
    {
        return $this->belongsToTenant($user, $letterType);
    }

    public function restore(User $user, LetterType $letterType): bool
    {
        return $this->belongsToTenant($user, $letterType);
    }

    public function forceDelete(User $user, LetterType $letterType): bool
    {
        return false;
    }

    private function belongsToTenant(User $user, LetterType $letterType): bool
    {
        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id !== null
            && $user->tenant_id === $letterType->tenant_id;
    }
}
