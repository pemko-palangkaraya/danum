<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Position;
use App\Models\PositionHolder;
use DateTimeImmutable;

class PositionHolderService
{
    public function current(Position $position, PositionService $positions): ?PositionHolder
    {
        $holder = $positions->getActiveHolder($position);
        $holder?->loadMissing('user');

        return $holder;
    }

    public function assign(
        Position $position,
        PositionService $positions,
        int $userId,
        string $startedAt,
        string $assignmentStatus,
    ): void {
        $positions->assignHolder(
            $position,
            $userId,
            new DateTimeImmutable($startedAt),
            $assignmentStatus,
        );
    }

    public function end(Position $position, PositionService $positions): void
    {
        $holder = $positions->getActiveHolder($position);

        if ($holder) {
            $positions->endHolder($holder, now());
        }
    }
}
