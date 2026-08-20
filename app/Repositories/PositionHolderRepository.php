<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PositionHolder;
use App\Repositories\Contracts\PositionHolderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use DateTimeInterface;

class PositionHolderRepository implements PositionHolderRepositoryInterface
{
    public function find(string $id): ?PositionHolder
    {
        return PositionHolder::query()->find($id);
    }

    public function getHistory(string $positionId): Collection
    {
        return PositionHolder::query()
            ->where('position_id', $positionId)
            ->orderBy('started_at')
            ->get();
    }

    public function findActive(string $positionId): ?PositionHolder
    {
        return PositionHolder::query()
            ->where('position_id', $positionId)
            ->whereNull('ended_at')
            ->first();
    }

    public function create(array $data): PositionHolder
    {
        return PositionHolder::query()->create($data);
    }

    public function end(
        PositionHolder $holder,
        DateTimeInterface $endedAt
    ): PositionHolder {
        $holder->update([
            'ended_at' => $endedAt,
        ]);

        return $holder->refresh();
    }

    // public function getActiveByUserId(string $userId): Collection
    public function getActiveByUserId(int $userId): Collection
    {
        return PositionHolder::query()
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->get();
    }
}
