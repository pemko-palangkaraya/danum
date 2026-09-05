<?php

namespace App\Services;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantCategory;
use App\Models\User;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly AuditLogService $auditLogService,
        private readonly UserRoleAssignmentService $roleAssignmentService,
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

    public function categories(): Collection
    {
        return TenantCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code']);
    }

    public function parentOptions(?string $categoryId = null, ?string $excludeTenantId = null): Collection
    {
        $categoryCode = $categoryId !== null && $categoryId !== ''
            ? TenantCategory::query()->whereKey($categoryId)->value('code')
            : null;

        $parentCategoryCodes = match ($categoryCode) {
            'kecamatan' => ['pemerintah-kota'],
            'kelurahan', 'desa' => ['kecamatan'],
            default => [],
        };

        if ($parentCategoryCodes === []) {
            return Tenant::query()->whereRaw('1 = 0')->get();
        }

        return Tenant::query()
            ->with('category')
            ->where('status', TenantStatus::ACTIVE)
            ->whereHas('category', fn ($query) => $query
                ->whereIn('code', $parentCategoryCodes)
                ->where('is_active', true))
            ->when($excludeTenantId, fn ($query) => $query->where('id', '<>', $excludeTenantId))
            ->orderBy('name')
            ->get(['id', 'name', 'province', 'city', 'district', 'village']);
    }

    public function create(array $data): Tenant
    {
        $this->validateParentHierarchy($data);
        $tenant = $this->tenantRepository->create($this->withDefaultCategory($data));

        $this->auditLogService->record(
            action: 'tenant.created',
            user: $this->actor(),
            auditable: $tenant,
            newValues: $this->tenantAuditValues($tenant),
            tenantId: $tenant->id,
        );

        return $tenant;
    }

    public function createWithInitialUser(array $tenantData, array $userData): Tenant
    {
        $this->validateParentHierarchy($tenantData);

        return DB::transaction(function () use ($tenantData, $userData): Tenant {
            $tenant = $this->tenantRepository->create($this->withDefaultCategory($tenantData));
            $administratorData = $this->roleAssignmentService->normalize([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'role' => 'tenant_admin',
                'tenant_id' => $tenant->id,
                'status' => 'active',
            ]);

            $administrator = User::query()->create($administratorData);

            $tenant->forceFill([
                'administrator_user_id' => $administrator->id,
            ])->save();

            $this->auditLogService->record(
                action: 'tenant.created',
                user: $this->actor(),
                auditable: $tenant,
                newValues: $this->tenantAuditValues($tenant->fresh()),
                tenantId: $tenant->id,
            );

            $this->auditLogService->record(
                action: 'user.created',
                user: $this->actor(),
                auditable: $administrator,
                newValues: $this->userAuditValues($administrator),
                tenantId: $tenant->id,
            );

            return $tenant->fresh(['administrator']);
        });
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $this->validateParentHierarchy($data, $tenant->id);
        $administrator = $tenant->administrator;
        $oldTenantValues = $this->tenantAuditValues($tenant);

        return DB::transaction(function () use ($tenant, $data, $administrator, $oldTenantValues): Tenant {
            $administratorData = $data['_administrator'] ?? null;
            unset($data['_administrator']);

            $updatedTenant = $this->tenantRepository->update($tenant, $data);

            $this->auditLogService->record(
                action: 'tenant.updated',
                user: $this->actor(),
                auditable: $updatedTenant,
                oldValues: $oldTenantValues,
                newValues: $this->tenantAuditValues($updatedTenant->fresh()),
                tenantId: $updatedTenant->id,
            );

            if (is_array($administratorData) && $administrator !== null) {
                $oldAdministratorValues = $this->userAuditValues($administrator);
                $administrator->update(array_filter([
                    'name' => $administratorData['name'] ?? null,
                    'email' => $administratorData['email'] ?? null,
                    'password' => $administratorData['password'] ?? null,
                    'status' => $administratorData['status'] ?? null,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''));

                $this->auditLogService->record(
                    action: 'user.updated',
                    user: $this->actor(),
                    auditable: $administrator,
                    oldValues: $oldAdministratorValues,
                    newValues: $this->userAuditValues($administrator->fresh()),
                    tenantId: $updatedTenant->id,
                );

                return $updatedTenant->fresh(['administrator']);
            }

            return $updatedTenant;
        });
    }

    public function delete(Tenant $tenant): bool
    {
        $deleted = $this->tenantRepository->delete($tenant);

        if ($deleted) {
            $this->auditLogService->record(
                action: 'tenant.deleted',
                user: $this->actor(),
                auditable: $tenant,
                oldValues: $this->tenantAuditValues($tenant),
                tenantId: $tenant->id,
            );
        }

        return $deleted;
    }

    public function restore(Tenant $tenant): bool
    {
        $restored = $this->tenantRepository->restore($tenant);

        if ($restored) {
            $this->auditLogService->record(
                action: 'tenant.restored',
                user: $this->actor(),
                auditable: $tenant,
                newValues: $this->tenantAuditValues($tenant->fresh()),
                tenantId: $tenant->id,
            );
        }

        return $restored;
    }

    public function findWithTrashed(string $id): ?Tenant
    {
        return $this->tenantRepository->findWithTrashed($id);
    }

    public function getAllWithTrashed(): Collection
    {
        return $this->tenantRepository->getAllWithTrashed();
    }

    private function validateParentHierarchy(array $data, ?string $tenantId = null): void
    {
        $categoryCode = TenantCategory::query()->whereKey($data['tenant_category_id'] ?? null)->value('code');
        $parentId = $data['parent_tenant_id'] ?? null;

        $requiredParentCategories = match ($categoryCode) {
            'kecamatan' => ['pemerintah-kota'],
            'kelurahan', 'desa' => ['kecamatan'],
            default => [],
        };

        if ($requiredParentCategories === []) {
            if ($parentId !== null && $parentId !== '') {
                throw ValidationException::withMessages([
                    'parent_tenant_id' => 'Kategori tenant ini tidak menggunakan parent wilayah.',
                ]);
            }

            return;
        }

        if ($parentId === null || $parentId === '') {
            throw ValidationException::withMessages([
                'parent_tenant_id' => 'Parent tenant wajib dipilih untuk kategori wilayah ini.',
            ]);
        }

        if ($tenantId !== null && $parentId === $tenantId) {
            throw ValidationException::withMessages([
                'parent_tenant_id' => 'Tenant tidak dapat menjadi parent untuk dirinya sendiri.',
            ]);
        }

        $parent = Tenant::query()
            ->with('category')
            ->whereKey($parentId)
            ->where('status', TenantStatus::ACTIVE)
            ->first();

        if (! $parent || ! in_array($parent->category?->code, $requiredParentCategories, true)) {
            throw ValidationException::withMessages([
                'parent_tenant_id' => 'Parent tenant tidak sesuai dengan hierarki kategori wilayah.',
            ]);
        }

        if ((string) ($data['province'] ?? '') !== (string) $parent->province
            || (string) ($data['city'] ?? '') !== (string) $parent->city) {
            throw ValidationException::withMessages([
                'parent_tenant_id' => 'Provinsi dan kota tenant harus mengikuti parent wilayah.',
            ]);
        }

        if ($categoryCode !== 'kecamatan'
            && (string) ($data['district'] ?? '') !== (string) $parent->district) {
            throw ValidationException::withMessages([
                'parent_tenant_id' => 'Kecamatan tenant harus mengikuti parent wilayah.',
            ]);
        }
    }

    private function withDefaultCategory(array $data): array
    {
        if (! empty($data['tenant_category_id'])) {
            return $data;
        }

        $defaultCategoryId = TenantCategory::query()
            ->where('code', 'lainnya')
            ->value('id');

        return $defaultCategoryId === null
            ? $data
            : [...$data, 'tenant_category_id' => $defaultCategoryId];
    }

    private function actor(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private function tenantAuditValues(Tenant $tenant): array
    {
        return [
            'code' => $tenant->code,
            'name' => $tenant->name,
            'tenant_category_id' => $tenant->tenant_category_id,
            'tenant_category' => $tenant->category?->name,
            'parent_tenant_id' => $tenant->parent_tenant_id,
            'parent_tenant' => $tenant->parent?->name,
            'province' => $tenant->province,
            'city' => $tenant->city,
            'district' => $tenant->district,
            'village' => $tenant->village,
            'address' => $tenant->address,
            'phone' => $tenant->phone,
            'email' => $tenant->email,
            'head_name' => $tenant->head_name,
            'head_title' => $tenant->head_title,
            'status' => $tenant->status?->value,
            'administrator_user_id' => $tenant->administrator_user_id,
        ];
    }

    private function userAuditValues(User $user): array
    {
        $role = $user->roleModel();

        return [
            'name' => $user->name,
            'nip' => $user->nip,
            'email' => $user->email,
            'platform_role' => $user->platform_role?->value,
            'role' => $role?->slug,
            'custom_role_id' => $user->custom_role_id,
            'custom_role' => $role?->name,
            'status' => $user->status?->value,
            'tenant_id' => $user->tenant_id,
        ];
    }
}
