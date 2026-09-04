<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PositionStatus;
use App\Models\Position;

class OutgoingLetterPositionService
{
    public function findAvailable(string $tenantId, string $positionId, string $capability): ?Position
    {
        return Position::query()
            ->where('tenant_id', $tenantId)
            ->where('status', PositionStatus::ACTIVE)
            ->where($capability, true)
            ->whereNull('deleted_at')
            ->whereHas('holders', fn ($query) => $query
                ->whereNull('ended_at')
                ->where('started_at', '<=', now()))
            ->with(['holders' => fn ($query) => $query
                ->whereNull('ended_at')
                ->where('started_at', '<=', now())
                ->with('user')])
            ->find($positionId);
    }
}
