<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class OutgoingLetterService
{
    public function __construct(
        private readonly OutgoingLetterRepositoryInterface $repository,
        private readonly LetterTypeService $letterTypeService,
        private readonly AuditLogService $auditLogService,
        private readonly OutgoingLetterWorkflowService $workflowService,
        private readonly OutgoingLetterIssuanceService $issuanceService,
        private readonly OutgoingLetterWithdrawalService $withdrawalService,
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
        return $this->workflowService->submit($letter, $changedBy);
    }

    public function reject(OutgoingLetter $letter, int $changedBy, string $reason): OutgoingLetter
    {
        return $this->workflowService->reject($letter, $changedBy, $reason);
    }

    public function cancel(OutgoingLetter $letter, int $changedBy, ?string $note = null): OutgoingLetter
    {
        return $this->workflowService->cancel($letter, $changedBy, $note);
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
        if (in_array($letter->status, [OutgoingLetterStatus::ISSUED, OutgoingLetterStatus::WITHDRAWN], true)) {
            throw new \DomainException('Issued or withdrawn letters cannot be restored or modified.');
        }

        $restored = $this->repository->restore($letter);

        if ($restored) {
            $fresh = $letter->fresh();
            $this->recordAudit('outgoing_letter.restored', $fresh, null, null, $this->auditValues($fresh));
        }

        return $restored;
    }

    public function validate(OutgoingLetter $letter, int $changedBy, ?string $note = null): OutgoingLetter
    {
        return $this->workflowService->validate($letter, $changedBy, $note);
    }

    public function issue(OutgoingLetter $letter, int $changedBy, ?string $note = null, ?string $pin = null): OutgoingLetter
    {
        return $this->issuanceService->issue($letter, $changedBy, $note, $pin);
    }

    public function requestWithdrawal(OutgoingLetter $letter, int $requestedBy, string $reason, string $statementPath)
    {
        return $this->withdrawalService->request($letter, $requestedBy, $reason, $statementPath);
    }

    public function approveWithdrawal($withdrawal, int $decidedBy, ?string $note = null)
    {
        return $this->withdrawalService->approve($withdrawal, $decidedBy, $note);
    }

    public function rejectWithdrawal($withdrawal, int $decidedBy, string $note)
    {
        return $this->withdrawalService->reject($withdrawal, $decidedBy, $note);
    }

    private function ensureMutable(OutgoingLetter $letter): void
    {
        if (in_array($letter->status, [OutgoingLetterStatus::ISSUED, OutgoingLetterStatus::WITHDRAWN], true)) {
            throw new \DomainException('Issued or withdrawn letters cannot be restored or modified.');
        }
    }

    private function recordAudit(string $action, OutgoingLetter $letter, ?int $actorId, ?array $oldValues, ?array $newValues): void
    {
        $actor = $actorId ? User::query()->find($actorId) : Auth::user();

        if ($actor) {
            $this->auditLogService->record($action, $actor, $letter, $oldValues, $newValues);
        }
    }

    private function auditValues(OutgoingLetter $letter): array
    {
        return [
            'status' => $letter->status?->value,
            'tenant_id' => $letter->tenant_id,
            'letter_type_id' => $letter->letter_type_id,
            'signer_position_id' => $letter->signer_position_id,
            'signer_user_id' => $letter->signer_user_id,
            'validator_position_id' => $letter->validator_position_id,
            'validator_user_id' => $letter->validator_user_id,
            'number' => $letter->number,
            'subject' => $letter->subject,
            'recipient_name' => $letter->recipient_name,
            'issued_at' => $letter->issued_at?->toDateString(),
            'valid_from' => $letter->valid_from?->toIso8601String(),
            'valid_until' => $letter->valid_until?->toIso8601String(),
            'signed_pdf_path' => $letter->signed_pdf_path,
            'signature_certificate_id' => $letter->signature_certificate_id,
            'signature_profile' => $letter->signature_profile,
            'signed_at' => $letter->signed_at?->toIso8601String(),
        ];
    }
}
