<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

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

    public function search(
        ?string $search = null,
        bool $onlyDeleted = false,
        int $perPage = 5,
    ): LengthAwarePaginator {
        return $this->tenantRepository->search(
            $search,
            $onlyDeleted,
            $perPage
        );
    }

    public function create(array $data): Tenant
    {
        return $this->tenantRepository->create($data);
    }

    public function createWithInitialUser(array $tenantData, array $userData): Tenant
    {
        return DB::transaction(function () use ($tenantData, $userData): Tenant {
            $tenant = $this->tenantRepository->create($tenantData);

            User::query()->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'role' => UserRole::TENANT_USER,
                'status' => UserStatus::ACTIVE,
                'tenant_id' => $tenant->id,
            ]);

            return $tenant;
        });
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        return $this->tenantRepository->update(
            $tenant,
            $data,
        );
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

    public function getAllWithTrashed(): Collection
    {
        return $this->tenantRepository->getAllWithTrashed();
    }
}
