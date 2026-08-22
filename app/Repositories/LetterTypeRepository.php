<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LetterType;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LetterTypeRepository implements LetterTypeRepositoryInterface
{
    public function find(string $id, string $tenantId): ?LetterType
    {
        return LetterType::query()
            ->whereNull('tenant_id')
            ->find($id);
    }

    public function getAll(string $tenantId): Collection
    {
        return LetterType::query()
            ->whereNull('tenant_id')
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

    public function findWithTrashed(string $id, string $tenantId): ?LetterType
    {
        return LetterType::withTrashed()
            ->whereNull('tenant_id')
            ->find($id);
    }
}
