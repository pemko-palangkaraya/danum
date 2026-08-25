<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Models\LetterTypePermission;
use App\Models\LetterTypeVersion;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LetterTypeService
{
    public function __construct(
        private readonly LetterTypeRepositoryInterface $repository,
        private readonly OutgoingLetterTemplateService $templateService,
    ) {}

    public function find(string $id, ?string $tenantId): ?LetterType { return $this->repository->find($id, $tenantId); }
    public function findWithTrashed(string $id, ?string $tenantId): ?LetterType { return $this->repository->findWithTrashed($id, $tenantId); }
    public function getAll(?string $tenantId): Collection { return $this->repository->getAll($tenantId); }

    public function getAvailableForTenant(string $tenantId): Collection
    {
        return LetterType::query()->where('status', 'active')->where(function ($query) use ($tenantId): void {
            $query->where('tenant_id', $tenantId)->orWhere(function ($global) use ($tenantId): void {
                $global->whereNull('tenant_id')->whereHas('permissions', function ($permission) use ($tenantId): void {
                    $permission->where('tenant_id', $tenantId)->where('allowed', true);
                });
            });
        })->get();
    }

    public function isAllowedForTenant(LetterType $letterType, string $tenantId): bool
    {
        if ($letterType->tenant_id === $tenantId) return true;
        if (! $letterType->isGlobal()) return false;
        return LetterTypePermission::query()->where('letter_type_id', $letterType->id)->where('tenant_id', $tenantId)->where('allowed', true)->exists();
    }

    public function grantTenantPermission(LetterType $letterType, string $tenantId): LetterTypePermission
    {
        if (! $letterType->isGlobal()) throw new \InvalidArgumentException('Only global letter types can be assigned to tenants.');
        return LetterTypePermission::query()->updateOrCreate(['letter_type_id' => $letterType->id, 'tenant_id' => $tenantId], ['allowed' => true]);
    }

    public function revokeTenantPermission(LetterType $letterType, string $tenantId): bool
    {
        if (! $letterType->isGlobal()) return false;
        return LetterTypePermission::query()->where('letter_type_id', $letterType->id)->where('tenant_id', $tenantId)->update(['allowed' => false]) > 0;
    }

    public function create(array $data): LetterType
    {
        return DB::transaction(function () use ($data): LetterType {
            if (($data['body_template'] ?? null) !== null) $this->templateService->validate((string) $data['body_template']);
            $letterType = $this->repository->create($data);
            $this->ensureCurrentVersion($letterType);
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

    public function delete(LetterType $letterType): bool { return $this->repository->delete($letterType); }
    public function restore(LetterType $letterType): bool { return $this->repository->restore($letterType); }
    public function currentVersion(LetterType $letterType): ?LetterTypeVersion { return $this->activeVersion($letterType); }

    public function activeVersion(LetterType $letterType, ?Carbon $at = null): ?LetterTypeVersion
    {
        $at ??= now();
        return LetterTypeVersion::query()->where('letter_type_id', $letterType->id)->where('is_active', true)
            ->where(function ($q) use ($at): void { $q->whereNull('effective_from')->orWhere('effective_from', '<=', $at); })
            ->where(function ($q) use ($at): void { $q->whereNull('effective_until')->orWhere('effective_until', '>', $at); })
            ->orderByDesc('version')->first();
    }

    public function createVersion(LetterType $letterType, array $data, int $createdBy): LetterTypeVersion
    {
        if (! $letterType->isGlobal()) {
            throw new \DomainException('Template version hanya dapat dikelola untuk jenis surat global.');
        }

        return DB::transaction(function () use ($letterType, $data, $createdBy): LetterTypeVersion {
            $effectiveFrom = Carbon::parse($data['effective_from'] ?? now());
            $effectiveUntil = !empty($data['effective_until']) ? Carbon::parse($data['effective_until']) : null;
            $latest = LetterTypeVersion::query()->where('letter_type_id', $letterType->id)->orderByDesc('version')->first();

            if ($latest?->effective_from && $effectiveFrom->lt($latest->effective_from)) {
                throw new \DomainException('Tanggal mulai versi baru tidak boleh lebih awal dari versi sebelumnya.');
            }
            if ($latest !== null && $effectiveFrom->lte($latest->effective_from)) {
                throw new \DomainException('Versi baru harus memiliki periode mulai setelah versi terakhir.');
            }
            if ($latest?->effective_until && $effectiveFrom->lt($latest->effective_until)) {
                throw new \DomainException('Periode versi baru tidak boleh bertumpang tindih dengan versi sebelumnya.');
            }
            if ($effectiveUntil !== null && $effectiveUntil->lte($effectiveFrom)) {
                throw new \DomainException('Tanggal selesai harus lebih besar dari tanggal mulai.');
            }

            if ($latest !== null && $latest->effective_until === null) {
                $latest->update(['effective_until' => $effectiveFrom]);
            }

            $version = LetterTypeVersion::query()->create([
                'letter_type_id' => $letterType->id,
                'version' => ($latest?->version ?? 0) + 1,
                'body_template' => (string) ($data['body_template'] ?? $letterType->body_template ?? ''),
                'template_path' => $data['template_path'] ?? $letterType->template_path,
                'effective_from' => $effectiveFrom,
                'effective_until' => $effectiveUntil,
                'is_active' => true,
                'change_note' => trim((string) ($data['change_note'] ?? '')) ?: null,
                'created_by' => $createdBy,
            ]);

            app(AuditLogService::class)->record(
                'letter_type.version.created',
                auth()->user(),
                $version,
                null,
                [
                    'letter_type_id' => $letterType->id,
                    'version' => $version->version,
                    'effective_from' => $version->effective_from?->toIso8601String(),
                    'effective_until' => $version->effective_until?->toIso8601String(),
                    'template_path' => $version->template_path,
                    'change_note' => $version->change_note,
                ],
            );

            return $version->refresh();
        });
    }

    public function ensureCurrentVersion(LetterType $letterType): ?LetterTypeVersion
    {
        $hasTemplate = $letterType->body_template !== null || $letterType->template_path !== null;
        if (! $hasTemplate) return null;

        $latest = LetterTypeVersion::query()->where('letter_type_id', $letterType->id)->orderByDesc('version')->first();
        $bodyTemplate = (string) ($letterType->body_template ?? '');
        $templatePath = $letterType->template_path;

        if ($latest !== null && $latest->body_template === $bodyTemplate && $latest->template_path === $templatePath) {
            return $latest;
        }

        $effectiveFrom = now();
        if ($latest !== null && $latest->effective_until === null) {
            $latest->update(['effective_until' => $effectiveFrom]);
        }

        return LetterTypeVersion::query()->create([
            'letter_type_id' => $letterType->id,
            'version' => ($latest?->version ?? 0) + 1,
            'body_template' => $bodyTemplate,
            'template_path' => $templatePath,
            'effective_from' => $effectiveFrom,
            'is_active' => true,
        ]);
    }
}
