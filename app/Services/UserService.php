<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatus;
use App\Events\UserStatusChanged;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserService
{
    private const EMPLOYEE_PROFILE_FIELDS = [
        'nip', 'pangkat', 'golongan', 'status_pegawai', 'tanggal_masuk', 'tanggal_pensiun',
    ];

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuditLogService $auditLogService,
        private readonly UserRoleAssignmentService $roleAssignmentService,
    ) {}

    public function find(int $id): ?User { return $this->userRepository->find($id); }
    public function findByEmail(string $email): ?User { return $this->userRepository->findByEmail($email); }
    public function getAll(): Collection { return $this->userRepository->getAll(); }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $profile = $this->extractEmployeeProfile($data);
            $user = $this->userRepository->create($this->normalizeRoleAssignment($data));
            if ($this->hasEmployeeProfileData($profile)) $user->employeeProfile()->create($profile);

            $this->auditLogService->record(
                action: 'user.created',
                user: $this->actor(),
                auditable: $user,
                newValues: $this->auditValues($user->fresh(['employeeProfile'])),
                tenantId: $user->tenant_id,
            );

            return $user->fresh(['employeeProfile']);
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $profile = $this->extractEmployeeProfile($data);
            $userData = $this->normalizeRoleAssignment($data);
            $oldStatus = $user->status;
            $newStatus = $userData['status'] ?? $oldStatus;
            $oldValues = $this->auditValues($user->fresh(['employeeProfile']));
            $changedAt = now();

            $updatedUser = $this->userRepository->update($user, $userData);

            if ($this->hasEmployeeProfileFields($data)) {
                if ($this->hasEmployeeProfileData($profile)) {
                    $updatedUser->employeeProfile()->updateOrCreate([], $profile);
                } else {
                    $updatedUser->employeeProfile()->delete();
                }
            }

            $updatedUser = $updatedUser->fresh(['employeeProfile']);
            $this->auditLogService->record(
                action: 'user.updated',
                user: $this->actor(),
                auditable: $updatedUser,
                oldValues: $oldValues,
                newValues: $this->auditValues($updatedUser),
                tenantId: $updatedUser->tenant_id,
            );

            if ($oldStatus === UserStatus::ACTIVE && $newStatus === UserStatus::INACTIVE) {
                UserStatusChanged::dispatch($updatedUser, $oldStatus, $newStatus, $changedAt);
            }

            return $updatedUser;
        });
    }

    public function delete(User $user): bool
    {
        $deleted = $this->userRepository->delete($user);
        if ($deleted) {
            $this->auditLogService->record(
                action: 'user.deleted',
                user: $this->actor(),
                auditable: $user,
                oldValues: $this->auditValues($user->fresh(['employeeProfile'])),
                tenantId: $user->tenant_id,
            );
        }
        return $deleted;
    }

    private function normalizeRoleAssignment(array $data): array
    {
        return $this->roleAssignmentService->normalize($this->withoutEmployeeProfileFields($data));
    }

    private function extractEmployeeProfile(array $data): array
    {
        return array_intersect_key($data, array_flip(self::EMPLOYEE_PROFILE_FIELDS));
    }

    private function withoutEmployeeProfileFields(array $data): array
    {
        return array_diff_key($data, array_flip(self::EMPLOYEE_PROFILE_FIELDS));
    }

    private function hasEmployeeProfileFields(array $data): bool
    {
        return array_intersect(array_keys($data), self::EMPLOYEE_PROFILE_FIELDS) !== [];
    }

    private function hasEmployeeProfileData(array $profile): bool
    {
        return collect($profile)->contains(fn (mixed $value): bool => filled($value));
    }

    private function actor(): ?User
    {
        $user = Auth::user();
        return $user instanceof User ? $user : null;
    }

    private function auditValues(User $user): array
    {
        $role = $user->effectiveRole();
        $profile = $user->employeeProfile;

        return [
            'name' => $user->name,
            'employee_profile' => $profile ? [
                'nip' => $profile->nip,
                'pangkat' => $profile->pangkat,
                'golongan' => $profile->golongan,
                'status_pegawai' => $profile->status_pegawai,
                'tanggal_masuk' => $profile->tanggal_masuk?->toDateString(),
                'tanggal_pensiun' => $profile->tanggal_pensiun?->toDateString(),
            ] : null,
            'email' => $user->email,
            'platform_role' => $user->platform_role?->value,
            'role' => $role?->slug,
            'custom_role_id' => $user->custom_role_id,
            'custom_role' => $role?->name,
            'status' => $user->status?->value,
            'tenant_id' => $user->tenant_id,
        ];
    }
}
