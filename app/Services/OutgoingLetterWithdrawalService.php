<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterWithdrawalRequest;
use App\Models\User;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use Illuminate\Support\Facades\DB;

class OutgoingLetterWithdrawalService
{
    public function __construct(
        private readonly OutgoingLetterRepositoryInterface $repository,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function request(OutgoingLetter $letter, int $requestedBy, string $reason, string $statementPath): OutgoingLetterWithdrawalRequest
    {
        $reason = trim($reason);
        $statementPath = trim($statementPath);

        if ($letter->status !== OutgoingLetterStatus::ISSUED) {
            throw new \DomainException('Hanya surat yang sudah diterbitkan yang dapat ditarik.');
        }
        if ($reason === '') {
            throw new \DomainException('Alasan penarikan wajib diisi.');
        }
        if ($statementPath === '') {
            throw new \DomainException('Surat pernyataan penarikan wajib dilampirkan.');
        }

        $pending = OutgoingLetterWithdrawalRequest::query()
            ->where('outgoing_letter_id', $letter->id)
            ->where('status', OutgoingLetterWithdrawalStatus::PENDING)
            ->exists();

        if ($pending) {
            throw new \DomainException('Pengajuan penarikan untuk surat ini masih menunggu keputusan.');
        }

        return DB::transaction(function () use ($letter, $requestedBy, $reason, $statementPath): OutgoingLetterWithdrawalRequest {
            $withdrawal = OutgoingLetterWithdrawalRequest::query()->create([
                'outgoing_letter_id' => $letter->id,
                'requested_by' => $requestedBy,
                'requested_at' => now(),
                'reason' => $reason,
                'statement_path' => $statementPath,
                'status' => OutgoingLetterWithdrawalStatus::PENDING,
            ]);

            $this->recordAudit(
                'outgoing_letter.withdrawal_requested',
                $letter,
                $requestedBy,
                null,
                ['status' => 'pending', 'reason' => $reason],
            );

            return $withdrawal;
        });
    }

    public function approve(OutgoingLetterWithdrawalRequest $withdrawal, int $decidedBy, ?string $note = null): OutgoingLetter
    {
        $this->ensureWithdrawalDecider($decidedBy);
        $note = trim((string) ($note ?? ''));

        $letter = $withdrawal->outgoingLetter()->lockForUpdate()->firstOrFail();

        if ($withdrawal->status !== OutgoingLetterWithdrawalStatus::PENDING) {
            throw new \DomainException('Pengajuan penarikan sudah diputuskan.');
        }
        if ($letter->status !== OutgoingLetterStatus::ISSUED) {
            throw new \DomainException('Hanya surat issued yang dapat ditarik.');
        }

        return DB::transaction(function () use ($withdrawal, $letter, $decidedBy, $note): OutgoingLetter {
            $withdrawal->forceFill([
                'status' => OutgoingLetterWithdrawalStatus::APPROVED,
                'decided_by' => $decidedBy,
                'decided_at' => now(),
                'decision_note' => $note,
            ])->save();

            $oldValues = $this->auditValues($letter);
            $letter = $this->repository->update($letter, ['status' => OutgoingLetterStatus::WITHDRAWN]);

            $this->recordAudit(
                'outgoing_letter.withdrawn',
                $letter,
                $decidedBy,
                $oldValues,
                $this->auditValues($letter),
            );

            return $letter;
        });
    }

    public function reject(OutgoingLetterWithdrawalRequest $withdrawal, int $decidedBy, string $note): OutgoingLetterWithdrawalRequest
    {
        $this->ensureWithdrawalDecider($decidedBy);
        $note = trim($note);

        if ($note === '') {
            throw new \DomainException('Catatan keputusan wajib diisi.');
        }
        if ($withdrawal->status !== OutgoingLetterWithdrawalStatus::PENDING) {
            throw new \DomainException('Pengajuan penarikan sudah diputuskan.');
        }

        $withdrawal->forceFill([
            'status' => OutgoingLetterWithdrawalStatus::REJECTED,
            'decided_by' => $decidedBy,
            'decided_at' => now(),
            'decision_note' => $note,
        ])->save();

        return $withdrawal;
    }

    private function ensureWithdrawalDecider(int $userId): void
    {
        if (! User::query()->findOrFail($userId)->isSuperAdmin()) {
            throw new \DomainException('Hanya Super Admin yang dapat memutuskan penarikan.');
        }
    }

    private function recordAudit(string $action, OutgoingLetter $letter, int $actorId, ?array $oldValues, ?array $newValues): void
    {
        $actor = User::query()->find($actorId);
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
