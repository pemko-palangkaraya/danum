<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {
    }

    public function find(string $id): ?Tenant
    {
        return $this->tenantRepository->find($id);
    }

    public function findByCode(string $code): ?Tenant
    {
        return $this->tenantRepository->findByCode($code);
    }

    public function getAll(): Collection
    {
        return $this->tenantRepository->getAll();
    }

    public function create(array $data): Tenant
    {
        return $this->tenantRepository->create($data);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        return $this->tenantRepository->update($tenant, $data);
    }

    public function delete(Tenant $tenant): bool
    {
        return $this->tenantRepository->delete($tenant);
    }

    public function restore(Tenant $tenant): bool
    {
        return $this->tenantRepository->restore($tenant);
    }

    public function findWithTrashed(string $id): ?Tenant
    {
        return $this->tenantRepository->findWithTrashed($id);
    }
}