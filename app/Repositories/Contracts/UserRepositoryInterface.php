<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    // public function find(string $id): ?User;
    public function find(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function getAll(): Collection;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function delete(User $user): bool;
}