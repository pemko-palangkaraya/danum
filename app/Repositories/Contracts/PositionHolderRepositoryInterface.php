<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\PositionHolder;
use Illuminate\Database\Eloquent\Collection;

interface PositionHolderRepositoryInterface
{
    public function find(string $id): ?PositionHolder;

    public function getHistory(string $positionId): Collection;

    public function findActive(string $positionId): ?PositionHolder;

    public function create(array $data): PositionHolder;

    public function end(PositionHolder $holder, \DateTimeInterface $endedAt): PositionHolder;

    // public function getActiveByUserId(string $userId): Collection;
    public function getActiveByUserId(int $userId): Collection;
}
