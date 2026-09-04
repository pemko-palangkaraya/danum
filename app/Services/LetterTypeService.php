<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Models\LetterTypePermission;
use App\Models\LetterTypeVersion;
use App\Models\User;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LetterTypeService
{
    public function __construct(
        private readonly LetterTypeRepositoryInterface $repository,
        private readonly DocxTemplateService $templateService,
        private readonly AuditLogService $auditLogService,
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
        if ($letterType->tenant_id === $tenantId) {
            return true;
        }

        if (! $letterType->isGlobal()) {
            return false;
        }

        return $this->tenantPermissionQuery($letterType, $tenantId)->exists();
    }

    public function grantTenantPermission(LetterType $letterType, string $tenantId): LetterTypePermission
    {
        $this->ensureGlobal($letterType, 'Only global letter types can be assigned to tenants.');

        return LetterTypePermission::query()->updateOrCreate(
            ['letter_type_id' => $letterType->id, 'tenant_id' => $tenantId, 'tenant_category_id' => null],
            ['allowed' => true],
        );
    }

    public function revokeTenantPermission(LetterType $letterType, string $tenantId): bool
    {
        if (! $letterType->isGlobal()) {
            return false;
        }

        return LetterTypePermission::query()
            ->where('letter_type_id', $letterType->id)
            ->where('tenant_id', $tenantId)
            ->whereNull('tenant_category_id')
            ->update(['allowed' => false]) > 0;
    }

    public function grantCategoryPermission(LetterType $letterType, int $categoryId): LetterTypePermission
    {
        $this->ensureGlobal($letterType, 'Only global letter types can be assigned to tenant categories.');

        return LetterTypePermission::query()->updateOrCreate(
            ['letter_type_id' => $letterType->id, 'tenant_category_id' => $categoryId, 'tenant_id' => null],
            ['allowed' => true],
        );
    }

    public function revokeCategoryPermission(LetterType $letterType, int $categoryId): bool
    {
        if (! $letterType->isGlobal()) {
            return false;
        }

        return LetterTypePermission::query()
            ->where('letter_type_id', $letterType->id)
            ->where('tenant_category_id', $categoryId)
            ->whereNull('tenant_id')
            ->update(['allowed' => false]) > 0;
    }

    public function create(array $data): LetterType
    {
        return DB::transaction(function () use ($data): LetterType {
            $this->validateTemplate($data);
            $letterType = $this->repository->create($data);

            if ($this->hasTemplate($data)) {
                $this->ensureCurrentVersion($letterType);
            }

            return $letterType->refresh();
        });
    }

    public function update(LetterType $letterType, array $data): LetterType
    {
        return DB::transaction(function () use ($letterType, $data): LetterType {
            $templateChanged = array_key_exists('body_template', $data) && $data['body_template'] !== $letterType->body_template;
            $pathChanged = array_key_exists('template_path', $data) && $data['template_path'] !== $letterType->template_path;

            if ($templateChanged && $data['body_template'] !== null) {
                $this->templateService->validate((string) $data['body_template']);
            }

            $letterType = $this->repository->update($letterType, $data);

            if ($templateChanged || $pathChanged) {
                $this->ensureCurrentVersion($letterType);
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
        return $this->activeVersion($letterType);
    }

    public function activeVersion(LetterType $letterType, ?Carbon $at = null): ?LetterTypeVersion
    {
        $at ??= now();

        return LetterTypeVersion::query()
            ->where('letter_type_id', $letterType->id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhere('effective_from', '<=', $at))
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhere('effective_until', '>', $at))
            ->orderByDesc('version')
            ->first();
    }

    public function createVersion(LetterType $letterType, array $data, int $createdBy): LetterTypeVersion
    {
        $this->ensureGlobal($letterType, 'Template version hanya dapat dikelola untuk jenis surat global.');

        return DB::transaction(function () use ($letterType, $data, $createdBy): LetterTypeVersion {
            $effectiveFrom = $this->normalizeVersionDate($data['effective_from'] ?? now());
            $effectiveUntil = ! empty($data['effective_until'])
                ? $this->normalizeVersionDate($data['effective_until'])
                : null;
            $latest = $this->latestVersion($letterType);

            if ($latest?->effective_from && $effectiveFrom->lte($latest->effective_from)) {
                throw new \DomainException('Versi baru harus memiliki periode mulai setelah versi terakhir.');
            }
            if ($latest?->effective_until && $effectiveFrom->lt($latest->effective_until)) {
                throw new \DomainException('Periode versi baru tidak boleh bertumpang tindih dengan versi sebelumnya.');
            }
            if ($effectiveUntil !== null && $effectiveUntil->lte($effectiveFrom)) {
                throw new \DomainException('Tanggal selesai harus lebih besar dari tanggal mulai.');
            }

            $variables = $this->normalizeVariables($data['variables'] ?? $letterType->variables ?? []);
            $currentVariables = $this->normalizeVariables($letterType->variables ?? []);
            $missingFromVersion = array_values(array_diff($currentVariables, $variables));

            if ($missingFromVersion) {
                throw new \DomainException('Versi baru tidak boleh menghapus variabel yang sudah tersedia pada jenis surat: ' . implode(', ', $missingFromVersion) . '.');
            }

            if ($latest !== null && $latest->effective_until === null) {
                $latest->update(['effective_until' => $effectiveFrom]);
            }

            $version = LetterTypeVersion::query()->create([
                'letter_type_id' => $letterType->id,
                'version' => ($latest?->version ?? 0) + 1,
                'body_template' => (string) ($data['body_template'] ?? $letterType->body_template ?? ''),
                'template_path' => $data['template_path'] ?? $letterType->template_path,
                'variables' => $variables,
                'effective_from' => $effectiveFrom,
                'effective_until' => $effectiveUntil,
                'is_active' => true,
                'change_note' => trim((string) ($data['change_note'] ?? '')) ?: null,
                'created_by' => $createdBy,
            ]);

            $this->auditLogService->record(
                'letter_type.version.created',
                User::query()->find($createdBy),
                $version,
                null,
                [
                    'letter_type_id' => $version->letter_type_id,
                    'version' => $version->version,
                    'template_path' => $version->template_path,
                    'variables' => $version->variables,
                    'effective_from' => $version->effective_from?->toIso8601String(),
                    'effective_until' => $version->effective_until?->toIso8601String(),
                    'change_note' => $version->change_note,
                ],
            );

            return $version;
        });
    }

    public function ensureCurrentVersion(LetterType $letterType): ?LetterTypeVersion
    {
        $latest = $this->latestVersion($letterType);
        $bodyTemplate = (string) ($letterType->body_template ?? '');
        $templatePath = $letterType->template_path;
        $variables = $this->normalizeVariables($letterType->variables ?? []);
        $active = $this->activeVersion($letterType);

        if ($active !== null && $active->body_template === $bodyTemplate && $active->template_path === $templatePath) {
            return $active;
        }

        if ($latest !== null && $latest->body_template === $bodyTemplate && $latest->template_path === $templatePath) {
            return $active ?? $latest;
        }

        if ($letterType->body_template === null && $letterType->template_path === null) {
            return null;
        }

        $effectiveFrom = $this->normalizeVersionDate(now());

        if ($latest !== null && $latest->effective_until === null && $latest->effective_from !== null && $effectiveFrom->gte($latest->effective_from)) {
            $latest->update(['effective_until' => $effectiveFrom]);
        }

        return LetterTypeVersion::query()->create([
            'letter_type_id' => $letterType->id,
            'version' => ($latest?->version ?? 0) + 1,
            'body_template' => $bodyTemplate,
            'template_path' => $templatePath,
            'variables' => $variables,
            'effective_from' => $effectiveFrom,
            'is_active' => true,
        ]);
    }

    private function tenantPermissionQuery(LetterType $letterType, string $tenantId)
    {
        return LetterTypePermission::query()
            ->where('letter_type_id', $letterType->id)
            ->where('allowed', true)
            ->where(function ($query) use ($tenantId): void {
                $query
                    ->where('tenant_id', $tenantId)
                    ->orWhereHas('category.tenants', fn ($tenants) => $tenants->whereKey($tenantId));
            });
    }

    private function latestVersion(LetterType $letterType): ?LetterTypeVersion
    {
        return LetterTypeVersion::query()
            ->where('letter_type_id', $letterType->id)
            ->orderByDesc('version')
            ->first();
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

    private function ensureGlobal(LetterType $letterType, string $message): void
    {
        if (! $letterType->isGlobal()) {
            throw new \InvalidArgumentException($message);
        }
    }

    private function normalizeVersionDate(mixed $value): Carbon
    {
        $date = $value instanceof CarbonInterface ? $value->copy() : Carbon::parse($value);

        return $date->startOfSecond();
    }

    private function normalizeVariables(array $variables): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            $variables,
        ))));
    }
}
