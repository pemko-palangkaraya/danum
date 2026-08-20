<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Position;
use App\Repositories\Contracts\PositionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PositionRepository implements PositionRepositoryInterface
{
    public function find(string $id): ?Position
    {
        return Position::query()->find($id);
    }

    public function findByCode(string $tenantId, string $code): ?Position
    {
        return Position::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    public function getAll(string $tenantId): Collection
    {
        return Position::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Position
    {
        return Position::query()->create($data);
    }

    public function update(Position $position, array $data): Position
    {
        $position->update($data);

        return $position->refresh();
    }

    public function delete(Position $position): bool
    {
        return (bool) $position->delete();
    }

    public function restore(Position $position): bool
    {
        return (bool) $position->restore();
    }

    public function findWithTrashed(string $id): ?Position
    {
        return Position::withTrashed()->find($id);
    }
}
