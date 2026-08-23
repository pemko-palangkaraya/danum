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
    public function viewAny(User $user): bool { return $user->isSuperAdmin() || $user->isTenantUser(); }
    public function view(User $user, OutgoingLetter $outgoingLetter): bool { return $user->isSuperAdmin() || $this->belongsToTenant($user, $outgoingLetter); }
    public function create(User $user): bool { return $user->isTenantUser(); }

    public function update(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter) && $outgoingLetter->status === OutgoingLetterStatus::DRAFT && $outgoingLetter->submitted_at === null && (int) $outgoingLetter->created_by === (int) $user->id;
    }

    public function delete(User $user, OutgoingLetter $outgoingLetter): bool { return $this->update($user, $outgoingLetter); }
    public function restore(User $user, OutgoingLetter $outgoingLetter): bool { return $user->isSuperAdmin() && $outgoingLetter->status !== OutgoingLetterStatus::ISSUED; }

    public function submit(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter) && $user->status === UserStatus::ACTIVE && $outgoingLetter->status === OutgoingLetterStatus::DRAFT && $outgoingLetter->submitted_at === null && (int) $outgoingLetter->created_by === (int) $user->id;
    }

    public function validate(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter) && $user->status === UserStatus::ACTIVE && $outgoingLetter->status === OutgoingLetterStatus::DRAFT && $outgoingLetter->submitted_at !== null && $outgoingLetter->validator_user_id !== null && (int) $outgoingLetter->validator_user_id === (int) $user->id;
    }

    public function issue(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter) && $user->status === UserStatus::ACTIVE && $outgoingLetter->status === OutgoingLetterStatus::VALIDATED && $outgoingLetter->signer_user_id !== null && (int) $outgoingLetter->signer_user_id === (int) $user->id;
    }

    public function reject(User $user, OutgoingLetter $outgoingLetter): bool
    {
        if (! $this->belongsToTenant($user, $outgoingLetter) || $user->status !== UserStatus::ACTIVE) return false;
        if ($outgoingLetter->status === OutgoingLetterStatus::DRAFT && $outgoingLetter->submitted_at !== null) return (int) $outgoingLetter->validator_user_id === (int) $user->id;
        if ($outgoingLetter->status === OutgoingLetterStatus::VALIDATED) return (int) $outgoingLetter->signer_user_id === (int) $user->id;
        return false;
    }

    public function cancel(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter) && (int) $outgoingLetter->created_by === (int) $user->id && $outgoingLetter->status === OutgoingLetterStatus::DRAFT && $outgoingLetter->submitted_at === null;
    }

    public function requestWithdrawal(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $this->belongsToTenant($user, $outgoingLetter) && (int) $outgoingLetter->created_by === (int) $user->id && $outgoingLetter->status === OutgoingLetterStatus::ISSUED;
    }

    public function decideWithdrawal(User $user, OutgoingLetter $outgoingLetter): bool
    {
        return $user->isSuperAdmin() && $outgoingLetter->status === OutgoingLetterStatus::ISSUED;
    }

    private function belongsToTenant(User $user, OutgoingLetter $outgoingLetter): bool { return $user->isTenantUser() && $user->tenant_id === $outgoingLetter->tenant_id; }
}
