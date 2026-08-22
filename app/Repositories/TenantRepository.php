<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TenantRepository implements TenantRepositoryInterface
{
    public function find(string $id): ?Tenant
    {
        return Tenant::query()->find($id);
    }

    public function findByCode(string $code): ?Tenant
    {
        return Tenant::query()
            ->where('code', $code)
            ->first();
    }

    public function getAll(): Collection
    {
        return Tenant::query()
            ->get();
    }

    public function search(
        ?string $search = null,
        bool $onlyDeleted = false,
        int $perPage = 5,
        // ): Collection { ini sepertinya dipakai jika ingin tampil semua
    ): LengthAwarePaginator {
        $query = $onlyDeleted
            ? Tenant::onlyTrashed()
            : Tenant::query();

        $search = trim((string) $search);

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('code', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%")
                    ->orWhere('province', 'ilike', "%{$search}%");
            });
        }

        // return $query->get(); ini pasangan untuk Collection
        return $query
            ->latest()
            ->paginate($perPage);
    }


    public function create(array $data): Tenant
    {
        return Tenant::query()->create($data);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant->refresh();
    }

    public function delete(Tenant $tenant): bool
    {
        return $tenant->delete();
    }

    public function restore(Tenant $tenant): bool
    {
        return $tenant->restore();
    }

    public function findWithTrashed(string $id): ?Tenant
    {
        return Tenant::withTrashed()->find($id);
    }

    public function getAllWithTrashed(): Collection
    {
        return Tenant::withTrashed()->get();
    }

    public function getPaginated(
        ?string $search = null,
        bool $withTrashed = false,
        int $perPage = 10,
    ): LengthAwarePaginator {
        $query = Tenant::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        $search = trim((string) $search);

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('code', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%")
                    ->orWhere('province', 'ilike', "%{$search}%");
            });
        }

        return $query
            ->latest()
            ->paginate($perPage);
    }
}
