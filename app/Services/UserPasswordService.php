<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class UserPasswordService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function change(User $user, string $currentPassword, string $newPassword): User
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => 'Password saat ini tidak sesuai.',
            ]);
        }

        return $this->set($user, $newPassword, 'user.password_changed');
    }

    public function reset(User $user, string $newPassword): User
    {
        return $this->set($user, $newPassword, 'user.password_reset');
    }

    private function set(User $user, string $newPassword, string $action): User
    {
        $user->forceFill([
            'password' => $newPassword,
        ])->save();

        $this->auditLogService->record(
            action: $action,
            user: auth()->user(),
            auditable: $user,
            newValues: ['password_changed' => true],
            tenantId: $user->tenant_id,
        );

        return $user->fresh();
    }
}
