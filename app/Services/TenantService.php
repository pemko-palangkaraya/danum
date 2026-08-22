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

    public function search(?string $search = null, bool $onlyDeleted = false, int $perPage = 5): LengthAwarePaginator
    {
        return $this->tenantRepository->search($search, $onlyDeleted, $perPage);
    }

    public function create(array $data): Tenant
    {
        return $this->tenantRepository->create($data);
    }

    public function createWithInitialUser(array $tenantData, array $userData): Tenant
    {
        return DB::transaction(function () use ($tenantData, $userData): Tenant {
            $tenant = $this->tenantRepository->create($tenantData);

            $administrator = User::query()->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'role' => UserRole::TENANT_USER,
                'status' => UserStatus::ACTIVE,
                'tenant_id' => $tenant->id,
            ]);

            $tenant->forceFill([
                'administrator_user_id' => $administrator->id,
            ])->save();

            return $tenant->fresh(['administrator']);
        });
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $administrator = $tenant->administrator;

        return DB::transaction(function () use ($tenant, $data, $administrator): Tenant {
            $administratorData = $data['_administrator'] ?? null;
            unset($data['_administrator']);

            $updatedTenant = $this->tenantRepository->update($tenant, $data);

            if (is_array($administratorData) && $administrator !== null) {
                $administrator->update(array_filter([
                    'name' => $administratorData['name'] ?? null,
                    'email' => $administratorData['email'] ?? null,
                    'password' => $administratorData['password'] ?? null,
                    'status' => $administratorData['status'] ?? null,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''));
            }

            return $updatedTenant->fresh(['administrator']);
        });
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
