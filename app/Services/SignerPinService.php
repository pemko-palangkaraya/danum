<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SignerPinService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    public function set(User $user, string $pin): void
    {
        $pin = trim($pin);

        if (! preg_match('/^\d{6}$/', $pin)) {
            throw new \DomainException('PIN penandatangan harus terdiri dari 6 digit.');
        }

        $user->forceFill([
            'signing_pin_hash' => Hash::make($pin),
            'signing_pin_set_at' => now(),
            'signing_pin_failed_attempts' => 0,
            'signing_pin_locked_until' => null,
        ])->save();
    }

    public function verify(User $user, string $pin): void
    {
        if ($user->signing_pin_locked_until?->isFuture()) {
            throw new \DomainException('PIN penandatangan terkunci sementara. Silakan coba lagi nanti.');
        }

        if (! preg_match('/^\d{6}$/', trim($pin)) || blank($user->signing_pin_hash) || ! Hash::check($pin, $user->signing_pin_hash)) {
            $attempts = (int) $user->signing_pin_failed_attempts + 1;
            $lockedUntil = null;

            if ($attempts >= self::MAX_ATTEMPTS) {
                $lockedUntil = now()->addMinutes(self::LOCK_MINUTES);
                $attempts = 0;
            }

            $user->forceFill([
                'signing_pin_failed_attempts' => $attempts,
                'signing_pin_locked_until' => $lockedUntil,
            ])->save();

            throw new \DomainException('PIN penandatangan tidak valid.');
        }

        $user->forceFill([
            'signing_pin_failed_attempts' => 0,
            'signing_pin_locked_until' => null,
        ])->save();
    }

    public function hasPin(User $user): bool
    {
        return filled($user->signing_pin_hash);
    }

    public function status(User $user): array
    {
        return [
            'configured' => $this->hasPin($user),
            'locked' => $user->signing_pin_locked_until?->isFuture() ?? false,
            'locked_until' => $user->signing_pin_locked_until,
            'set_at' => $user->signing_pin_set_at,
        ];
    }
}
