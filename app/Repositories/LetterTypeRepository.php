<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LetterType;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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

    public function scheduleDeletion(LetterType $letterType, \DateTimeInterface $at): bool
    {
        return $letterType->update(['deletion_scheduled_at' => $at]);
    }

    public function processScheduledDeletions(): int
    {
        return DB::transaction(function (): int {
            $letterTypes = LetterType::query()
                ->whereNotNull('deletion_scheduled_at')
                ->where('deletion_scheduled_at', '<=', now())
                ->get();

            $count = 0;

            foreach ($letterTypes as $letterType) {
                if ($letterType->delete()) {
                    $count++;
                }
            }

            return $count;
        });
    }

    public function restore(LetterType $letterType): bool
    {
        return DB::transaction(function () use ($letterType): bool {
            $letterType->update(['deletion_scheduled_at' => null]);

            return $letterType->restore();
        });
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
