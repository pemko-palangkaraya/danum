<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Repositories\Contracts\PositionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class PositionService
{
    public function __construct(
        private readonly PositionRepositoryInterface $positionRepository,
        private readonly PositionHolderService $holderService,
    ) {}

    public function find(string $id): ?Position { return $this->positionRepository->find($id); }
    public function findByCode(string $tenantId, string $code): ?Position { return Position::query()->where('code', $code)->whereHas('category.tenants', fn ($q) => $q->whereKey($tenantId))->first(); }
    public function getAll(string $tenantId): Collection { return Position::query()->whereHas('category.tenants', fn ($q) => $q->whereKey($tenantId))->orderBy('sort_order')->orderBy('name')->get(); }

    public function create(array $data): Position
    {
        if (empty($data['tenant_category_id'])) throw new InvalidArgumentException('Tenant category is required for a position master.');
        $this->validateParent($data['tenant_category_id'], $data['parent_id'] ?? null);
        return $this->positionRepository->create($data);
    }

    public function update(Position $position, array $data): Position
    {
        return DB::transaction(function () use ($position, $data): Position {
            $currentStatus = $position->status;
            $newStatus = $data['status'] ?? $currentStatus;
            $isBecomingInactive = $currentStatus === PositionStatus::ACTIVE && $newStatus === PositionStatus::INACTIVE;
            if (array_key_exists('parent_id', $data) || array_key_exists('tenant_category_id', $data)) $this->validateParent($data['tenant_category_id'] ?? $position->tenant_category_id, $data['parent_id'] ?? $position->parent_id, $position);
            $updatedPosition = $this->positionRepository->update($position, $data);
            if ($isBecomingInactive) $this->holderService->endActive($position);
            return $updatedPosition->refresh();
        });
    }

    public function delete(Position $position): bool
    {
        return DB::transaction(function () use ($position): bool {
            if ($position->children()->exists()) throw new LogicException('Jabatan yang masih memiliki bawahan tidak dapat dihapus. Pindahkan bawahannya terlebih dahulu.');
            $this->holderService->endActive($position);
            return $this->positionRepository->delete($position);
        });
    }

    public function restore(Position $position): bool { return $this->positionRepository->restore($position); }

    private function validateParent(string $categoryId, ?string $parentId, ?Position $current = null): void
    {
        if (! $parentId) return;
        if ($current && (string) $current->id === (string) $parentId) throw new InvalidArgumentException('Jabatan tidak dapat menjadi atasan dirinya sendiri.');
        $parent = Position::query()->find($parentId);
        if (! $parent || (string) $parent->tenant_category_id !== (string) $categoryId) throw new InvalidArgumentException('Atasan jabatan harus berada pada organisasi yang sama.');
        if ($current) { $cursor = $parent; while ($cursor->parent_id) { if ((string) $cursor->parent_id === (string) $current->id) throw new InvalidArgumentException('Struktur jabatan tidak boleh membentuk siklus.'); $cursor = $cursor->parent; if (! $cursor) break; } }
    }
}
