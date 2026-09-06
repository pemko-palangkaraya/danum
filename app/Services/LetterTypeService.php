<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Models\LetterTypePermission;
use App\Models\LetterTypeVersion;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LetterTypeService
{
    public function __construct(
        private readonly LetterTypeRepositoryInterface $repository,
        private readonly DocxTemplateService $templateService,
        private readonly LetterTypePermissionService $permissionService,
        private readonly LetterTypeVersionService $versionService,
    ) {}

    public function find(string $id, ?string $tenantId): ?LetterType
    {
        return $this->repository->find($id, $tenantId);
    }

    public function findWithTrashed(string $id, ?string $tenantId): ?LetterType
    {
        return $this->repository->findWithTrashed($id, $tenantId);
    }

    public function getAll(?string $tenantId): Collection
    {
        return $this->repository->getAll($tenantId);
    }

    public function getAvailableForTenant(string $tenantId): Collection
    {
        return LetterType::query()
            ->where('status', 'active')
            ->where(function ($query) use ($tenantId): void {
                $query
                    ->where('tenant_id', $tenantId)
                    ->orWhere(function ($global) use ($tenantId): void {
                        $global
                            ->whereNull('tenant_id')
                            ->whereHas('permissions', function ($permission) use ($tenantId): void {
                                $permission
                                    ->where('allowed', true)
                                    ->where(function ($scope) use ($tenantId): void {
                                        $scope
                                            ->where('tenant_id', $tenantId)
                                            ->orWhereHas('category.tenants', fn ($tenants) => $tenants->whereKey($tenantId));
                                    });
                            });
                    });
            })
            ->get();
    }

    public function isAllowedForTenant(LetterType $letterType, string $tenantId): bool
    {
        return $this->permissionService->isAllowedForTenant($letterType, $tenantId);
    }

    public function grantTenantPermission(LetterType $letterType, string $tenantId): LetterTypePermission
    {
        return $this->permissionService->grantTenant($letterType, $tenantId);
    }

    public function revokeTenantPermission(LetterType $letterType, string $tenantId): bool
    {
        return $this->permissionService->revokeTenant($letterType, $tenantId);
    }

    public function grantCategoryPermission(LetterType $letterType, int $categoryId): LetterTypePermission
    {
        return $this->permissionService->grantCategory($letterType, $categoryId);
    }

    public function revokeCategoryPermission(LetterType $letterType, int $categoryId): bool
    {
        return $this->permissionService->revokeCategory($letterType, $categoryId);
    }

    public function create(array $data): LetterType
    {
        return DB::transaction(function () use ($data): LetterType {
            $this->validateTemplate($data);
            $letterType = $this->repository->create($data);

            if ($this->hasTemplate($data)) {
                $this->versionService->ensureCurrent($letterType);
            }

            return $letterType->refresh();
        });
    }

    public function update(LetterType $letterType, array $data): LetterType
    {
        return DB::transaction(function () use ($letterType, $data): LetterType {
            $templateChanged = array_key_exists('body_template', $data)
                && $data['body_template'] !== $letterType->body_template;
            $pathChanged = array_key_exists('template_path', $data)
                && $data['template_path'] !== $letterType->template_path;

            if ($templateChanged && $data['body_template'] !== null) {
                $this->templateService->validate((string) $data['body_template']);
            }

            $letterType = $this->repository->update($letterType, $data);

            if ($templateChanged || $pathChanged) {
                $this->versionService->ensureCurrent($letterType);
            }

            return $letterType;
        });
    }

    public function delete(LetterType $letterType): bool
    {
        return $this->repository->delete($letterType);
    }

    public function restore(LetterType $letterType): bool
    {
        return $this->repository->restore($letterType);
    }

    public function currentVersion(LetterType $letterType): ?LetterTypeVersion
    {
        return $this->versionService->current($letterType);
    }

    public function activeVersion(LetterType $letterType, ?Carbon $at = null): ?LetterTypeVersion
    {
        return $this->versionService->active($letterType, $at);
    }

    public function createVersion(LetterType $letterType, array $data, int $createdBy): LetterTypeVersion
    {
        return $this->versionService->create($letterType, $data, $createdBy);
    }

    public function ensureCurrentVersion(LetterType $letterType): ?LetterTypeVersion
    {
        return $this->versionService->ensureCurrent($letterType);
    }

    private function validateTemplate(array $data): void
    {
        if (($data['body_template'] ?? null) !== null) {
            $this->templateService->validate((string) $data['body_template']);
        }
    }

    private function hasTemplate(array $data): bool
    {
        return ! empty($data['body_template']) || ! empty($data['template_path']);
    }
}
