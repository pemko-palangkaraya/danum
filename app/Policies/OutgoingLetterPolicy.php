<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OutgoingLetterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OutgoingLetter;
use App\Models\User;

class OutgoingLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SUPER_ADMIN || ($user->role === UserRole::TENANT_USER && $user->tenant_id !== null);
    }

    public function view(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $user->role === UserRole::SUPER_ADMIN || $this->belongsToTenant($user, $outgoingLetter);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::TENANT_USER && $user->tenant_id !== null;
    }

    public function update(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter) && $this->isEditable($outgoingLetter);
    }

    public function delete(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter) && $this->isEditable($outgoingLetter);
    }

    public function restore(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $user->role === UserRole::SUPER_ADMIN && $outgoingLetter->status !== OutgoingLetterStatus::ISSUED;
    }

    public function validate(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter)
            && $user->status === UserStatus::ACTIVE
            && $outgoingLetter->status === OutgoingLetterStatus::DRAFT
            && $outgoingLetter->validator_user_id !== null
            && (int) $outgoingLetter->validator_user_id === (int) $user->id;
    }

    public function issue(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter)
            && $user->status === UserStatus::ACTIVE
            && $outgoingLetter->status === OutgoingLetterStatus::VALIDATED
            && $outgoingLetter->signer_user_id !== null
            && (int) $outgoingLetter->signer_user_id === (int) $user->id;
    }

    public function cancel(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter)
            && in_array($outgoingLetter->status, [OutgoingLetterStatus::DRAFT, OutgoingLetterStatus::VALIDATED], true);
    }

    private function belongsToTenant(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $user->role === UserRole::TENANT_USER && $user->tenant_id === $outgoingLetter->tenant_id;
    }

    private function isEditable(OutgoingLetter $outgoingLetter): bool
    {
        return in_array($outgoingLetter->status, [OutgoingLetterStatus::DRAFT, OutgoingLetterStatus::VALIDATED], true);
    }
}
