<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Position;
use Illuminate\Database\Eloquent\Collection;

interface PositionRepositoryInterface
{
    public function find(string $id): ?Position;

    public function getAll(string $tenantId): Collection;

    public function create(array $data): Position;

    public function update(Position $position, array $data): Position;

    public function delete(Position $position): bool;

    public function restore(Position $position): bool;

    public function findWithTrashed(string $id): ?Position;
}
