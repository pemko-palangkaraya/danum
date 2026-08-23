<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LetterType;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LetterTypeRepository implements LetterTypeRepositoryInterface
{
    public function find(string $id, ?string $tenantId): ?LetterType
    {
        return LetterType::query()
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id');

                if ($tenantId !== null) {
                    $query->orWhere('tenant_id', $tenantId);
                }
            })
            ->find($id);
    }

    public function getAll(?string $tenantId): Collection
    {
        return LetterType::query()
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id');

                if ($tenantId !== null) {
                    $query->orWhere('tenant_id', $tenantId);
                }
            })
            ->get();
    }

    public function create(array $data): LetterType
    {
        return LetterType::query()->create($data);
    }

    public function update(LetterType $letterType, array $data): LetterType
    {
        $letterType->update($data);

        return $letterType->refresh();
    }

    public function delete(LetterType $letterType): bool
    {
        return $letterType->delete();
    }

    public function restore(LetterType $letterType): bool
    {
        return $letterType->restore();
    }

    public function findWithTrashed(string $id, ?string $tenantId): ?LetterType
    {
        return LetterType::withTrashed()
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id');

                if ($tenantId !== null) {
                    $query->orWhere('tenant_id', $tenantId);
                }
            })
            ->find($id);
    }
}
