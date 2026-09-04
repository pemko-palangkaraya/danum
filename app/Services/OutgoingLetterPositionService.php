<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PositionStatus;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;

class OutgoingLetterPositionService
{
    public function findAvailable(string $tenantId, string $positionId, string $capability): ?Position
    {
        return $this->availableForTenant($tenantId, $capability)->find($positionId);
    }

    public function availableForTenant(string $tenantId, string $capability): Builder
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
                ->with('user')]);
    }

    public function availableForTenantCategory(string $tenantId, string|int|null $tenantCategoryId, string $capability): Builder
    {
        return Position::query()
            ->where('tenant_category_id', $tenantCategoryId)
            ->where('status', PositionStatus::ACTIVE)
            ->where($capability, true)
            ->whereHas('holders', fn ($query) => $query
                ->where('tenant_id', $tenantId)
                ->whereNull('ended_at')
                ->where('started_at', '<=', now()))
            ->with(['holders' => fn ($query) => $query
                ->where('tenant_id', $tenantId)
                ->whereNull('ended_at')
                ->where('started_at', '<=', now())
                ->with('user')]);
    }
}
