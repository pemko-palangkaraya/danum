<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\OutgoingLetter;
use App\Models\User;

class OutgoingLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::TENANT_USER && $user->tenant_id !== null;
    }

    public function view(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter);
    }

    public function delete(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter);
    }

    public function restore(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter);
    }

    public function validate(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter);
    }

    public function issue(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter);
    }

    public function cancel(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter);
    }

    private function belongsToTenant(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $user->role === UserRole::TENANT_USER
            && $user->tenant_id === $outgoingLetter->tenant_id;
    }
}
