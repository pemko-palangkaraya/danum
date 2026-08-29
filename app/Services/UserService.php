<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatus;
use App\Events\UserStatusChanged;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    public function getAll(): Collection
    {
        return $this->userRepository->getAll();
    }

    public function create(array $data): User
    {
        $user = $this->userRepository->create($data);

        $this->auditLogService->record(
            action: 'user.created',
            user: $this->actor(),
            auditable: $user,
            newValues: $this->auditValues($user),
            tenantId: $user->tenant_id,
        );

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $oldStatus = $user->status;
        $newStatus = $data['status'] ?? $oldStatus;
        $oldValues = $this->auditValues($user);

        $changedAt = now();

        $updatedUser = $this->userRepository->update($user, $data);

        $this->auditLogService->record(
            action: 'user.updated',
            user: $this->actor(),
            auditable: $updatedUser,
            oldValues: $oldValues,
            newValues: $this->auditValues($updatedUser->fresh()),
            tenantId: $updatedUser->tenant_id,
        );

        if (
            $oldStatus === UserStatus::ACTIVE
            && $newStatus === UserStatus::INACTIVE
        ) {
            UserStatusChanged::dispatch(
                $updatedUser,
                $oldStatus,
                $newStatus,
                $changedAt
            );
        }

        return $updatedUser;
    }

    public function delete(User $user): bool
    {
        $deleted = $this->userRepository->delete($user);

        if ($deleted) {
            $this->auditLogService->record(
                action: 'user.deleted',
                user: $this->actor(),
                auditable: $user,
                oldValues: $this->auditValues($user),
                tenantId: $user->tenant_id,
            );
        }

        return $deleted;
    }

    private function actor(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private function auditValues(User $user): array
    {
        return [
            'name' => $user->name,
            'nip' => $user->nip,
            'email' => $user->email,
            'role' => $user->role?->value,
            'custom_role_id' => $user->custom_role_id,
            'custom_role' => $user->customRole?->name,
            'status' => $user->status?->value,
            'tenant_id' => $user->tenant_id,
        ];
    }
}
