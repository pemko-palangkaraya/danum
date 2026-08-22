<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TenantRepositoryInterface
{
    public function find(string $id): ?Tenant;

    public function findByCode(string $code): ?Tenant;

    public function getAll(): Collection;

    public function create(array $data): Tenant;

    public function update(Tenant $tenant, array $data): Tenant;

    public function delete(Tenant $tenant): bool;

    public function restore(Tenant $tenant): bool;

    public function findWithTrashed(string $id): ?Tenant;

    public function getAllWithTrashed(): Collection;

    public function getPaginated(
        ?string $search = null,
        bool $withTrashed = false,
        int $perPage = 5,
    ): LengthAwarePaginator;

    public function search(
        ?string $search = null,
        bool $onlyDeleted = false,
        int $perPage = 10,
        // ): Collection; ini sepertinya dipakai jika ingin tampil semua
    ): LengthAwarePaginator;
}
