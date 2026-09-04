<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PositionAssignmentStatus;
use App\Enums\PositionStatus;
use App\Enums\UserStatus;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Repositories\Contracts\PositionHolderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class PositionHolderService
{
    public function __construct(
        private readonly PositionHolderRepositoryInterface $holderRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function assign(Position $position, int $userId, \DateTimeInterface $startedAt, string|PositionAssignmentStatus $assignmentStatus = PositionAssignmentStatus::DEFINITIF, ?string $appointmentNumber = null, ?string $appointmentDocumentPath = null): PositionHolder
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
                    $this->end($currentHolder, $startedAt);
                }
            }
            return $this->holderRepository->create([
                'position_id' => $position->id, 'tenant_id' => $user->tenant_id, 'user_id' => $userId,
                'assignment_status' => $status->value, 'appointment_number' => $appointmentNumber ? trim($appointmentNumber) : null,
                'appointment_document_path' => $appointmentDocumentPath, 'started_at' => $startedAt, 'ended_at' => null,
            ]);
        });
    }

    public function end(PositionHolder $holder, \DateTimeInterface $endedAt): PositionHolder
    {
        if ($holder->ended_at !== null) throw new LogicException('Position holder has already ended.');
        if ($endedAt < $holder->started_at) throw new InvalidArgumentException('Holder end date cannot be earlier than start date.');
        return $this->holderRepository->end($holder, $endedAt);
    }

    public function active(Position $position): ?PositionHolder
    {
        $tenantId = auth()->user()?->tenant_id;
        $query = $position->holders()->whereNull('ended_at');
        if ($tenantId) $query->where('tenant_id', $tenantId);
        return $query->latest('started_at')->first();
    }

    public function activeMany(Position $position): Collection
    {
        $tenantId = auth()->user()?->tenant_id;
        return $position->holders()->with('user')->whereNull('ended_at')->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->orderBy('started_at')->orderBy('id')->get();
    }

    public function history(Position $position): Collection
    {
        return $position->holders()->with('user', 'tenant')->orderByDesc('started_at')->get();
    }
}
