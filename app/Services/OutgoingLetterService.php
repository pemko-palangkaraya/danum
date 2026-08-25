<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterWithdrawalRequest;
use App\Models\User;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use App\Repositories\Contracts\OutgoingLetterStatusHistoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OutgoingLetterService
{
    public function __construct(
        private readonly OutgoingLetterRepositoryInterface $repository,
        private readonly OutgoingLetterStatusHistoryRepositoryInterface $historyRepository,
        private readonly LetterTypeService $letterTypeService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function getAll(string $tenantId): Collection
    {
        return $this->repository->getAll($tenantId);
    }

    public function find(string $id, string $tenantId): ?OutgoingLetter
    {
        return $this->repository->find($id, $tenantId);
    }

    public function findWithTrashed(string $id, string $tenantId): ?OutgoingLetter
    {
        return $this->repository->findWithTrashed($id, $tenantId);
    }

    public function create(array $data, int $changedBy): OutgoingLetter
    {
        $actor = User::query()->findOrFail($changedBy);
        $letterType = $this->letterTypeService->find((string) $data['letter_type_id'], (string) $data['tenant_id']);

        if (! $letterType) {
            throw new \DomainException('Jenis surat tidak ditemukan.');
        }

        if ($letterType->status->value !== 'active') {
            throw new \DomainException('Jenis surat tidak aktif.');
        }

        if (! $actor->isSuperAdmin() && ! $this->letterTypeService->isAllowedForTenant($letterType, (string) $data['tenant_id'])) {
            throw new \DomainException('Jenis surat tidak diizinkan untuk unit ini.');
        }

        if (empty($data['letter_type_version_id']) && ($version = $this->letterTypeService->ensureCurrentVersion($letterType))) {
            $data['letter_type_version_id'] = $version->id;
        }

        $letter = $this->repository->create($data);
        $this->recordHistory($letter, 'created', $changedBy);
        $this->recordAudit('outgoing_letter.created', $letter, $changedBy, null, $this->auditValues($letter));

        return $letter;
    }

    public function update(OutgoingLetter $letter, array $data): OutgoingLetter
    {
        $this->ensureMutable($letter);

        if ($letter->status === OutgoingLetterStatus::DRAFT && $letter->submitted_at !== null) {
            throw new \DomainException('Surat sudah dikirim untuk verifikasi dan tidak dapat diedit.');
        }

        $oldValues = $this->auditValues($letter);
        $updated = $this->repository->update($letter, $data);
        $this->recordAudit('outgoing_letter.updated', $updated, null, $oldValues, $this->auditValues($updated->fresh()));

        return $updated;
    }

    public function submit(OutgoingLetter $letter, int $changedBy): OutgoingLetter
    {
        if ($letter->status !== OutgoingLetterStatus::DRAFT) {
            throw new \DomainException('Hanya draft yang dapat dikirim untuk verifikasi.');
        }

        if ($letter->submitted_at !== null) {
            throw new \DomainException('Surat sudah dikirim untuk verifikasi.');
        }

        if ($letter->validator_user_id === null) {
            throw new \DomainException('Verifikator belum ditentukan.');
        }

        $oldValues = $this->auditValues($letter);
        $letter = $this->repository->update($letter, [
            'submitted_at' => now(),
            'rejection_reason' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ]);
        $this->recordHistory($letter, 'submitted', $changedBy);
        $this->recordAudit('outgoing_letter.submitted', $letter, $changedBy, $oldValues, $this->auditValues($letter));

        return $letter;
    }

    public function delete(OutgoingLetter $letter): bool
    {
        $this->ensureMutable($letter);

        if ($letter->submitted_at !== null) {
            throw new \DomainException('Surat yang sudah dikirim untuk verifikasi tidak dapat dihapus.');
        }

        $oldValues = $this->auditValues($letter);
        $deleted = $this->repository->delete($letter);

        if ($deleted) {
            $this->recordAudit('outgoing_letter.deleted', $letter, null, $oldValues, null);
        }

        return $deleted;
    }

    public function restore(OutgoingLetter $letter): bool
    {
        if ($letter->status === OutgoingLetterStatus::ISSUED) {
            throw new \DomainException('Issued letters cannot be restored or modified.');
        }

        $restored = $this->repository->restore($letter);

        if ($restored) {
            $this->recordAudit('outgoing_letter.restored', $letter->fresh(), null, null, $this->auditValues($letter->fresh()));
        }

        return $restored;
    }

    public function validate(OutgoingLetter $letter, int $changedBy): OutgoingLetter
    {
        if ($letter->status !== OutgoingLetterStatus::DRAFT || $letter->submitted_at === null) {
            throw new \DomainException('Surat belum dikirim untuk verifikasi.');
        }

        return $this->transition(
            $letter,
            OutgoingLetterStatus::DRAFT,
            OutgoingLetterStatus::VALIDATED,
            $changedBy,
            ['submitted_at' => null],
            'outgoing_letter.validated',
        );
    }

    public function issue(OutgoingLetter $letter, int $changedBy): OutgoingLetter
    {
        $letterType = $letter->letterType()->first();
        $issuedAt = now();
        $attributes = [
            'issued_at' => $issuedAt->toDateString(),
            'valid_from' => $issuedAt,
            'valid_until' => null,
        ];

        $period = $letterType?->validity_period ?? 'none';

        if ($period !== 'none') {
            $attributes['valid_until'] = match ($period) {
                '1_week' => $issuedAt->copy()->addWeek(),
                '2_weeks' => $issuedAt->copy()->addWeeks(2),
                '1_month' => $issuedAt->copy()->addMonth(),
                '3_months' => $issuedAt->copy()->addMonths(3),
                '6_months' => $issuedAt->copy()->addMonths(6),
                '1_year' => $issuedAt->copy()->addYear(),
                default => throw new \DomainException('Masa berlaku jenis surat tidak valid.'),
            };
        }

        return $this->transition(
            $letter,
            OutgoingLetterStatus::VALIDATED,
            OutgoingLetterStatus::ISSUED,
            $changedBy,
            $attributes,
            'outgoing_letter.issued',
        );
    }

    public function reject(OutgoingLetter $letter, int $changedBy, string $reason): OutgoingLetter
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new \DomainException('Alasan penolakan wajib diisi.');
        }

        if (! in_array($letter->status, [OutgoingLetterStatus::DRAFT, OutgoingLetterStatus::VALIDATED], true)) {
            throw new \DomainException('Surat tidak dapat ditolak pada status saat ini.');
        }

        $oldValues = $this->auditValues($letter);
        $letter = $this->repository->update($letter, [
            'status' => OutgoingLetterStatus::DRAFT,
            'submitted_at' => null,
            'rejection_reason' => $reason,
            'rejected_by' => $changedBy,
            'rejected_at' => now(),
        ]);
        $this->recordHistory($letter, 'rejected', $changedBy);
        $this->recordAudit('outgoing_letter.rejected', $letter, $changedBy, $oldValues, $this->auditValues($letter));

        return $letter;
    }

    public function cancel(OutgoingLetter $letter, int $changedBy): OutgoingLetter
    {
        if ($letter->status !== OutgoingLetterStatus::DRAFT || $letter->submitted_at !== null) {
            throw new \DomainException('Only editable drafts can be cancelled.');
        }

        return $this->transition(
            $letter,
            OutgoingLetterStatus::DRAFT,
            OutgoingLetterStatus::CANCELLED,
            $changedBy,
            [],
            'outgoing_letter.cancelled',
        );
    }

    public function requestWithdrawal(OutgoingLetter $letter, int $requestedBy, string $reason, string $statementPath): OutgoingLetterWithdrawalRequest
    {
        $reason = trim($reason);

        if ($letter->status !== OutgoingLetterStatus::ISSUED) {
            throw new \DomainException('Hanya surat yang sudah diterbitkan yang dapat diajukan untuk penarikan.');
        }

        if ($reason === '') {
            throw new \DomainException('Alasan penarikan wajib diisi.');
        }

        if (trim($statementPath) === '') {
            throw new \DomainException('Pernyataan penarikan wajib dilampirkan.');
        }

        if ($letter->withdrawalRequests()->where('status', OutgoingLetterWithdrawalStatus::PENDING)->exists()) {
            throw new \DomainException('Pengajuan penarikan sedang menunggu persetujuan.');
        }

        $request = OutgoingLetterWithdrawalRequest::query()->create([
            'outgoing_letter_id' => $letter->id,
            'requested_by' => $requestedBy,
            'requested_at' => now(),
            'reason' => $reason,
            'statement_path' => $statementPath,
            'status' => OutgoingLetterWithdrawalStatus::PENDING,
        ]);

        $this->recordHistory($letter, 'withdrawal_requested', $requestedBy);
        $this->recordAudit('outgoing_letter.withdrawal_requested', $letter, $requestedBy, null, [
            'status' => $letter->status?->value,
            'withdrawal_request_id' => $request->id,
            'reason' => $reason,
        ]);

        return $request;
    }

    public function approveWithdrawal(OutgoingLetterWithdrawalRequest $request, int $decidedBy, ?string $note = null): OutgoingLetter
    {
        return DB::transaction(function () use ($request, $decidedBy, $note): OutgoingLetter {
            $this->ensureWithdrawalDecider($decidedBy);

            if ($request->status !== OutgoingLetterWithdrawalStatus::PENDING) {
                throw new \DomainException('Pengajuan penarikan sudah diputuskan.');
            }

            $letter = $request->outgoingLetter()->lockForUpdate()->firstOrFail();

            if ($letter->status !== OutgoingLetterStatus::ISSUED) {
                throw new \DomainException('Surat tidak lagi dapat ditarik.');
            }

            $oldValues = $this->auditValues($letter);
            $request->update([
                'status' => OutgoingLetterWithdrawalStatus::APPROVED,
                'decided_by' => $decidedBy,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);
            $letter = $this->repository->update($letter, ['status' => OutgoingLetterStatus::WITHDRAWN]);
            $this->recordHistory($letter, 'withdrawn', $decidedBy);
            $this->recordAudit('outgoing_letter.withdrawn', $letter, $decidedBy, $oldValues, $this->auditValues($letter));

            return $letter;
        });
    }

    public function rejectWithdrawal(OutgoingLetterWithdrawalRequest $request, int $decidedBy, string $note): OutgoingLetterWithdrawalRequest
    {
        $this->ensureWithdrawalDecider($decidedBy);
        $note = trim($note);

        if ($request->status !== OutgoingLetterWithdrawalStatus::PENDING) {
            throw new \DomainException('Pengajuan penarikan sudah diputuskan.');
        }

        if ($note === '') {
            throw new \DomainException('Catatan penolakan wajib diisi.');
        }

        $request->update([
            'status' => OutgoingLetterWithdrawalStatus::REJECTED,
            'decided_by' => $decidedBy,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);

        $letter = $request->outgoingLetter;
        $this->recordHistory($letter, 'withdrawal_rejected', $decidedBy);
        $this->recordAudit('outgoing_letter.withdrawal_rejected', $letter, $decidedBy, null, [
            'status' => $letter->status?->value,
            'withdrawal_request_id' => $request->id,
            'decision_note' => $note,
        ]);

        return $request->refresh();
    }

    private function ensureWithdrawalDecider(int $userId): void
    {
        if (! User::query()->findOrFail($userId)->isSuperAdmin()) {
            throw new \DomainException('Hanya Super Admin yang dapat memutuskan penarikan.');
        }
    }

    private function transition(
        OutgoingLetter $letter,
        OutgoingLetterStatus $from,
        OutgoingLetterStatus $to,
        int $changedBy,
        array $attributes = [],
        ?string $auditAction = null,
    ): OutgoingLetter {
        if ($letter->status !== $from) {
            throw new \DomainException(sprintf('Letter must have %s status.', $from->value));
        }

        $oldValues = $this->auditValues($letter);
        $letter = $this->repository->update($letter, [...$attributes, 'status' => $to]);
        $this->recordHistory($letter, $to->value, $changedBy);

        if ($auditAction !== null) {
            $this->recordAudit($auditAction, $letter, $changedBy, $oldValues, $this->auditValues($letter));
        }

        return $letter;
    }

    private function ensureMutable(OutgoingLetter $letter): void
    {
        if ($letter->status !== OutgoingLetterStatus::DRAFT) {
            throw new \DomainException('Only draft letters can be modified.');
        }
    }

    private function recordHistory(OutgoingLetter $letter, string $action, int $changedBy): void
    {
        $this->historyRepository->create([
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $changedBy,
            'status' => $letter->status,
            'action' => $action,
        ]);
    }

    private function recordAudit(
        string $action,
        OutgoingLetter $letter,
        ?int $changedBy,
        ?array $oldValues,
        ?array $newValues,
    ): void {
        $actor = $changedBy !== null
            ? User::query()->find($changedBy)
            : Auth::user();

        $this->auditLogService->record(
            action: $action,
            user: $actor instanceof User ? $actor : null,
            auditable: $letter,
            oldValues: $oldValues,
            newValues: $newValues,
            tenantId: $letter->tenant_id,
        );
    }

    private function auditValues(OutgoingLetter $letter): array
    {
        return [
            'tenant_id' => $letter->tenant_id,
            'created_by' => $letter->created_by,
            'letter_type_id' => $letter->letter_type_id,
            'letter_type_version_id' => $letter->letter_type_version_id,
            'number' => $letter->number,
            'recipient_name' => $letter->recipient_name,
            'recipient_address' => $letter->recipient_address,
            'subject' => $letter->subject,
            'status' => $letter->status?->value,
            'submitted_at' => $letter->submitted_at?->toDateTimeString(),
            'issued_at' => $letter->issued_at?->toDateString(),
            'valid_from' => $letter->valid_from?->toDateTimeString(),
            'valid_until' => $letter->valid_until?->toDateTimeString(),
            'rejection_reason' => $letter->rejection_reason,
            'rejected_by' => $letter->rejected_by,
            'rejected_at' => $letter->rejected_at?->toDateTimeString(),
        ];
    }
}
