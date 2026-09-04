<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use App\Repositories\Contracts\OutgoingLetterStatusHistoryRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class OutgoingLetterWorkflowService
{
    public function __construct(
        private readonly OutgoingLetterRepositoryInterface $repository,
        private readonly OutgoingLetterStatusHistoryRepositoryInterface $historyRepository,
        private readonly AuditLogService $auditLogService,
    ) {}

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

    public function validate(OutgoingLetter $letter, int $changedBy, ?string $note = null): OutgoingLetter
    {
        $note = $this->requiredNote($note, 'Catatan verifikasi wajib diisi.');

        if ($letter->status !== OutgoingLetterStatus::DRAFT || $letter->submitted_at === null) {
            throw new \DomainException('Surat belum dikirim untuk verifikasi.');
        }

        if ($letter->validator_user_id !== $changedBy) {
            throw new \DomainException('Hanya verifikator yang ditentukan untuk surat ini yang dapat melakukan verifikasi.');
        }

        $oldValues = $this->auditValues($letter);
        $letter = $this->repository->update($letter, [
            'status' => OutgoingLetterStatus::VALIDATED,
            'submitted_at' => null,
            'verification_note' => $note,
        ]);

        $this->recordHistory($letter, 'validated', $changedBy, $note);
        $this->recordAudit('outgoing_letter.validated', $letter, $changedBy, $oldValues, $this->auditValues($letter));

        return $letter;
    }

    public function reject(OutgoingLetter $letter, int $changedBy, string $reason): OutgoingLetter
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \DomainException('Alasan penolakan wajib diisi.');
        }

        if ($letter->status !== OutgoingLetterStatus::VALIDATED) {
            throw new \DomainException('Hanya surat yang sudah divalidasi yang dapat ditolak.');
        }

        $oldValues = $this->auditValues($letter);
        $letter = $this->repository->update($letter, [
            'status' => OutgoingLetterStatus::DRAFT,
            'submitted_at' => null,
            'rejection_reason' => $reason,
            'rejected_by' => $changedBy,
            'rejected_at' => now(),
        ]);

        $this->recordHistory($letter, 'rejected', $changedBy, $reason);
        $this->recordAudit('outgoing_letter.rejected', $letter, $changedBy, $oldValues, $this->auditValues($letter));

        return $letter;
    }

    public function cancel(OutgoingLetter $letter, int $changedBy, ?string $note = null): OutgoingLetter
    {
        if ($letter->status !== OutgoingLetterStatus::DRAFT) {
            throw new \DomainException('Hanya draft yang dapat dibatalkan.');
        }

        if ($letter->submitted_at !== null) {
            throw new \DomainException('Surat yang sudah dikirim untuk verifikasi tidak dapat dibatalkan.');
        }

        $oldValues = $this->auditValues($letter);
        $letter = $this->repository->update($letter, [
            'status' => OutgoingLetterStatus::CANCELLED,
        ]);

        $this->recordHistory($letter, 'cancelled', $changedBy, $note);
        $this->recordAudit('outgoing_letter.cancelled', $letter, $changedBy, $oldValues, $this->auditValues($letter));

        return $letter;
    }

    private function requiredNote(?string $note, string $message): string
    {
        $note = trim((string) ($note ?? request()->input('note', '')));
        if ($note === '') {
            throw new \DomainException($message);
        }

        return $note;
    }

    private function recordHistory(OutgoingLetter $letter, string $action, int $changedBy, ?string $note = null): void
    {
        $this->historyRepository->create([
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $changedBy,
            'status' => $letter->status,
            'action' => $action,
            'note' => $note,
        ]);
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
