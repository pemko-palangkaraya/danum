<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
}