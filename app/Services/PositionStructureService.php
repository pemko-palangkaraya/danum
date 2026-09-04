<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PositionType;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\TenantPositionStructure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PositionStructureService
{
    public function tenantCategoryId(string $tenantId): mixed
    {
        return Tenant::query()->whereKey($tenantId)->value('tenant_category_id');
    }

    public function ensureRows(string $tenantId): void
    {
        $categoryId = $this->tenantCategoryId($tenantId);
        if (! $categoryId) {
            return;
        }

        $positions = Position::query()
            ->where('tenant_category_id', $categoryId)
            ->where('status', 'active')
            ->get(['id', 'sort_order']);

        foreach ($positions as $position) {
            TenantPositionStructure::query()->firstOrCreate(
                ['tenant_id' => $tenantId, 'position_id' => $position->id],
                ['parent_position_id' => null, 'sort_order' => $position->sort_order, 'is_root' => false],
            );
        }
    }

    public function positionForTenant(string $tenantId, string $positionId): Position
    {
        $position = Position::query()->with('category')->findOrFail($positionId);
        $categoryId = $this->tenantCategoryId($tenantId);

        if ($categoryId === null || (string) $position->tenant_category_id !== (string) $categoryId) {
            abort(403);
        }

        return $position;
    }

    public function structureForPosition(string $tenantId, string $positionId): TenantPositionStructure
    {
        return TenantPositionStructure::query()
            ->where('tenant_id', $tenantId)
            ->where('position_id', $positionId)
            ->firstOrFail();
    }

    public function save(
        string $tenantId,
        Position $position,
        ?string $parentPositionId,
        int $sortOrder,
        PositionType|string $positionType,
        bool $isRoot,
    ): void {
        $positionType = $positionType instanceof PositionType
            ? $positionType->value
            : $positionType;

        if ($parentPositionId !== null) {
            $parent = $this->positionForTenant($tenantId, $parentPositionId);

            if ((string) $parent->id === (string) $position->id) {
                throw new InvalidArgumentException('Jabatan tidak dapat menjadi atasan dirinya sendiri.');
            }

            if ($this->wouldCreateCycle($tenantId, $position->id, $parent->id)) {
                throw new InvalidArgumentException('Struktur tidak boleh membentuk siklus.');
            }
        }

        if ($isRoot && $parentPositionId !== null) {
            throw new InvalidArgumentException('Kepala organisasi tidak boleh memiliki atasan.');
        }

        DB::transaction(function () use ($tenantId, $position, $parentPositionId, $sortOrder, $positionType, $isRoot): void {
            $structure = TenantPositionStructure::query()->firstOrCreate(
                ['tenant_id' => $tenantId, 'position_id' => $position->id],
                ['sort_order' => 0, 'is_root' => false],
            );

            if ($isRoot) {
                TenantPositionStructure::query()
                    ->where('tenant_id', $tenantId)
                    ->where('position_id', '!=', $position->id)
                    ->update(['is_root' => false]);
            }

            $position->update(['position_type' => $positionType]);
            $structure->update([
                'parent_position_id' => $parentPositionId,
                'sort_order' => $sortOrder,
                'is_root' => $isRoot,
            ]);
        });
    }

    public function setRoot(string $tenantId, Position $position): void
    {
        DB::transaction(function () use ($tenantId, $position): void {
            TenantPositionStructure::query()
                ->where('tenant_id', $tenantId)
                ->update(['is_root' => false]);

            TenantPositionStructure::query()
                ->where('tenant_id', $tenantId)
                ->where('position_id', $position->id)
                ->update(['parent_position_id' => null, 'is_root' => true]);
        });
    }

    public function structures(string $tenantId): Collection
    {
        return TenantPositionStructure::query()
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('position_id');
    }

    private function wouldCreateCycle(string $tenantId, string $positionId, string $parentId): bool
    {
        $rows = TenantPositionStructure::query()
            ->where('tenant_id', $tenantId)
            ->pluck('parent_position_id', 'position_id');

        $cursor = $parentId;
        $seen = [];

        while ($cursor !== '') {
            if ($cursor === $positionId || isset($seen[$cursor])) {
                return true;
            }

            $seen[$cursor] = true;
            $cursor = (string) ($rows[$cursor] ?? '');
        }

        return false;
    }
}
