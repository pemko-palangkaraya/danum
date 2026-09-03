<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PositionAssignmentStatus;
use App\Enums\PositionStatus;
use App\Enums\PositionType;
use App\Enums\UserStatus;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Repositories\Contracts\PositionHolderRepositoryInterface;
use App\Repositories\Contracts\PositionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class PositionService
{
    public function __construct(
        private readonly PositionRepositoryInterface $positionRepository,
        private readonly PositionHolderRepositoryInterface $positionHolderRepository,
        private readonly UserRepositoryInterface $userRepository,
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
            $currentStatus = $position->status; $newStatus = $data['status'] ?? $currentStatus;
            $isBecomingInactive = $currentStatus === PositionStatus::ACTIVE && $newStatus === PositionStatus::INACTIVE;
            if (array_key_exists('parent_id', $data) || array_key_exists('tenant_category_id', $data)) $this->validateParent($data['tenant_category_id'] ?? $position->tenant_category_id, $data['parent_id'] ?? $position->parent_id, $position);
            $updatedPosition = $this->positionRepository->update($position, $data);
            if ($isBecomingInactive) foreach ($position->holders()->whereNull('ended_at')->get() as $activeHolder) $this->positionHolderRepository->end($activeHolder, now());
            return $updatedPosition->refresh();
        });
    }

    public function delete(Position $position): bool
    {
        return DB::transaction(function () use ($position): bool {
            if ($position->children()->exists()) throw new LogicException('Jabatan yang masih memiliki bawahan tidak dapat dihapus. Pindahkan bawahannya terlebih dahulu.');
            foreach ($position->holders()->whereNull('ended_at')->get() as $activeHolder) $this->positionHolderRepository->end($activeHolder, now());
            return $this->positionRepository->delete($position);
        });
    }

    public function restore(Position $position): bool { return $this->positionRepository->restore($position); }

    public function assignHolder(Position $position, int $userId, \DateTimeInterface $startedAt, string|PositionAssignmentStatus $assignmentStatus = PositionAssignmentStatus::DEFINITIF, ?string $appointmentNumber = null, ?string $appointmentDocumentPath = null): PositionHolder
    {
        return DB::transaction(function () use ($position, $userId, $startedAt, $assignmentStatus, $appointmentNumber, $appointmentDocumentPath): PositionHolder {
            if ($position->status !== PositionStatus::ACTIVE) throw new LogicException('Cannot assign a holder to an inactive position.');
            $status = $assignmentStatus instanceof PositionAssignmentStatus ? $assignmentStatus : PositionAssignmentStatus::tryFrom($assignmentStatus);
            if ($status === null) throw new InvalidArgumentException('Status pemangku jabatan tidak valid.');
            $user = $this->userRepository->find($userId);
            if ($user === null) throw new InvalidArgumentException('User not found.');
            if ($user->status !== UserStatus::ACTIVE) throw new LogicException('Inactive user cannot become a position holder.');
            if (! $user->tenant_id) throw new LogicException('User must belong to a tenant.');
            $categoryId = DB::table('tenants')->where('id', $user->tenant_id)->value('tenant_category_id');
            if ((string) $categoryId !== (string) $position->tenant_category_id) throw new LogicException('User and position must belong to the same tenant category.');
            $activeHolder = $position->holders()->where('tenant_id', $user->tenant_id)->whereNull('ended_at')->where('user_id', $userId)->first();
            if ($activeHolder !== null) throw new LogicException('User is already the active holder of this position.');
            if (! $position->allowsMultipleActiveHolders()) {
                $currentHolder = $position->holders()->where('tenant_id', $user->tenant_id)->whereNull('ended_at')->latest('started_at')->first();
                if ($currentHolder !== null) {
                    if ($startedAt < $currentHolder->started_at) throw new InvalidArgumentException('New holder start date cannot be earlier than the current holder start date.');
                    $this->positionHolderRepository->end($currentHolder, $startedAt);
                }
            }
            return $this->positionHolderRepository->create([
                'position_id' => $position->id, 'tenant_id' => $user->tenant_id, 'user_id' => $userId,
                'assignment_status' => $status->value, 'appointment_number' => $appointmentNumber ? trim($appointmentNumber) : null,
                'appointment_document_path' => $appointmentDocumentPath, 'started_at' => $startedAt, 'ended_at' => null,
            ]);
        });
    }

    public function endHolder(PositionHolder $holder, \DateTimeInterface $endedAt): PositionHolder
    {
        if ($holder->ended_at !== null) throw new LogicException('Position holder has already ended.');
        if ($endedAt < $holder->started_at) throw new InvalidArgumentException('Holder end date cannot be earlier than start date.');
        return $this->positionHolderRepository->end($holder, $endedAt);
    }
    public function getActiveHolder(Position $position): ?PositionHolder { $tenantId = auth()->user()?->tenant_id; $query = $position->holders()->whereNull('ended_at'); if ($tenantId) $query->where('tenant_id', $tenantId); return $query->latest('started_at')->first(); }
    public function getActiveHolders(Position $position): Collection { $tenantId = auth()->user()?->tenant_id; return $position->holders()->with('user')->whereNull('ended_at')->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->orderBy('started_at')->orderBy('id')->get(); }
    public function getHolderHistory(Position $position): Collection { return $position->holders()->with('user', 'tenant')->orderByDesc('started_at')->get(); }
    public function findWithTrashed(string $id): ?Position { return $this->positionRepository->findWithTrashed($id); }
    public function getActiveSignatoryPositions(string $tenantId): Collection { return Position::query()->whereHas('category.tenants', fn ($q) => $q->whereKey($tenantId))->where('status', PositionStatus::ACTIVE)->where('can_sign', true)->with(['holders' => fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('ended_at'), 'holders.user'])->orderBy('name')->get(); }

    private function validateParent(string $categoryId, ?string $parentId, ?Position $current = null): void
    {
        if (! $parentId) return;
        if ($current && (string) $current->id === (string) $parentId) throw new InvalidArgumentException('Jabatan tidak dapat menjadi atasan dirinya sendiri.');
        $parent = Position::query()->find($parentId);
        if (! $parent || (string) $parent->tenant_category_id !== (string) $categoryId) throw new InvalidArgumentException('Atasan jabatan harus berada pada organisasi yang sama.');
        if ($current) { $cursor = $parent; while ($cursor->parent_id) { if ((string) $cursor->parent_id === (string) $current->id) throw new InvalidArgumentException('Struktur jabatan tidak boleh membentuk siklus.'); $cursor = $cursor->parent; if (! $cursor) break; } }
    }
}
