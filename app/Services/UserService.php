<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserStatus;
use App\Events\UserStatusChanged;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
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
        return $this->userRepository->create($data);
    }

    public function update(User $user, array $data): User
    {
        // return $this->userRepository->update($user, $data);
        $oldStatus = $user->status;
        $newStatus = $data['status'] ?? $oldStatus;

        $changedAt = now();

        $updatedUser = $this->userRepository->update($user, $data);

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
        return $this->userRepository->delete($user);
    }
}
