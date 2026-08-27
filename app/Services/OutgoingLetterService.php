<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterWithdrawalRequest;
use App\Models\SignerCertificate;
use App\Models\User;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use App\Repositories\Contracts\OutgoingLetterStatusHistoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OutgoingLetterService
{
    public function __construct(
        private readonly OutgoingLetterRepositoryInterface $repository,
        private readonly OutgoingLetterStatusHistoryRepositoryInterface $historyRepository,
        private readonly LetterTypeService $letterTypeService,
        private readonly AuditLogService $auditLogService,
        private readonly DocxPdfService $docxPdfService,
        private readonly PdfSigningService $pdfSigningService,
        private readonly SignerPinService $signerPinService,
    ) {}

    public function getAll(string $tenantId): Collection { return $this->repository->getAll($tenantId); }
    public function find(string $id, string $tenantId): ?OutgoingLetter { return $this->repository->find($id, $tenantId); }
    public function findWithTrashed(string $id, string $tenantId): ?OutgoingLetter { return $this->repository->findWithTrashed($id, $tenantId); }

    public function create(array $data, int $changedBy): OutgoingLetter
    {
        $actor = User::query()->findOrFail($changedBy);
        $letterType = $this->letterTypeService->find((string) $data['letter_type_id'], (string) $data['tenant_id']);
        if (! $letterType) throw new \DomainException('Jenis surat tidak ditemukan.');
        if ($letterType->status->value !== 'active') throw new \DomainException('Jenis surat tidak aktif.');
        if (! $actor->isSuperAdmin() && ! $this->letterTypeService->isAllowedForTenant($letterType, (string) $data['tenant_id'])) throw new \DomainException('Jenis surat tidak diizinkan untuk unit ini.');
        if (empty($data['letter_type_version_id']) && ($version = $this->letterTypeService->ensureCurrentVersion($letterType))) $data['letter_type_version_id'] = $version->id;
        $letter = $this->repository->create($data);
        $this->recordHistory($letter, 'created', $changedBy);
        $this->recordAudit('outgoing_letter.created', $letter, $changedBy, null, $this->auditValues($letter));
        return $letter;
    }

    public function update(OutgoingLetter $letter, array $data): OutgoingLetter
    {
        $this->ensureMutable($letter);
        if ($letter->status === OutgoingLetterStatus::DRAFT && $letter->submitted_at !== null) throw new \DomainException('Surat sudah dikirim untuk verifikasi dan tidak dapat diedit.');
        $oldValues = $this->auditValues($letter);
        $updated = $this->repository->update($letter, $data);
        $this->recordAudit('outgoing_letter.updated', $updated, null, $oldValues, $this->auditValues($updated->fresh()));
        return $updated;
    }

    public function submit(OutgoingLetter $letter, int $changedBy): OutgoingLetter
    {
        if ($letter->status !== OutgoingLetterStatus::DRAFT) throw new \DomainException('Hanya draft yang dapat dikirim untuk verifikasi.');
        if ($letter->submitted_at !== null) throw new \DomainException('Surat sudah dikirim untuk verifikasi.');
        if ($letter->validator_user_id === null) throw new \DomainException('Verifikator belum ditentukan.');
        $oldValues = $this->auditValues($letter);
        $letter = $this->repository->update($letter, ['submitted_at' => now(), 'rejection_reason' => null, 'rejected_by' => null, 'rejected_at' => null]);
        $this->recordHistory($letter, 'submitted', $changedBy);
        $this->recordAudit('outgoing_letter.submitted', $letter, $changedBy, $oldValues, $this->auditValues($letter));
        return $letter;
    }

    public function delete(OutgoingLetter $letter): bool
    {
        $this->ensureMutable($letter);
        if ($letter->submitted_at !== null) throw new \DomainException('Surat yang sudah dikirim untuk verifikasi tidak dapat dihapus.');
        $oldValues = $this->auditValues($letter);
        $deleted = $this->repository->delete($letter);
        if ($deleted) $this->recordAudit('outgoing_letter.deleted', $letter, null, $oldValues, null);
        return $deleted;
    }

    public function restore(OutgoingLetter $letter): bool
    {
        if ($letter->status === OutgoingLetterStatus::ISSUED) throw new \DomainException('Issued letters cannot be restored or modified.');
        $restored = $this->repository->restore($letter);
        if ($restored) $this->recordAudit('outgoing_letter.restored', $letter->fresh(), null, null, $this->auditValues($letter->fresh()));
        return $restored;
    }

    public function validate(OutgoingLetter $letter, int $changedBy, ?string $note = null): OutgoingLetter
    {
        $note = $this->requiredWorkflowNote($note, 'Catatan verifikasi wajib diisi.');
        if ($letter->status !== OutgoingLetterStatus::DRAFT || $letter->submitted_at === null) throw new \DomainException('Surat belum dikirim untuk verifikasi.');
        if ($letter->validator_user_id !== $changedBy) throw new \DomainException('Hanya verifikator yang ditentukan untuk surat ini yang dapat melakukan verifikasi.');
        $oldValues = $this->auditValues($letter);
        $letter = $this->repository->update($letter, ['status' => OutgoingLetterStatus::VALIDATED, 'submitted_at' => null, 'verification_note' => $note]);
        $this->recordHistory($letter, 'validated', $changedBy, $note);
        $this->recordAudit('outgoing_letter.validated', $letter, $changedBy, $oldValues, $this->auditValues($letter));
        return $letter;
    }

    public function issue(OutgoingLetter $letter, int $changedBy, ?string $note = null, ?string $pin = null): OutgoingLetter
    {
        $note = $this->requiredWorkflowNote($note, 'Catatan penandatanganan wajib diisi.');
        if ($letter->status !== OutgoingLetterStatus::VALIDATED) throw new \DomainException('Hanya surat yang sudah divalidasi yang dapat diterbitkan.');
        if ($letter->signer_user_id !== $changedBy) throw new \DomainException('Hanya penanda tangan yang ditentukan untuk surat ini yang dapat menerbitkan surat.');

        $signer = User::query()->findOrFail($changedBy);
        if (blank($pin)) throw new \DomainException('PIN penandatangan wajib diisi.');
        $this->signerPinService->verify($signer, $pin);

        $letterType = $letter->letterType()->first();
        $signerCertificate = $this->resolveSignerCertificate($letter);
        $issuedAt = now();
        $attributes = [
            'issued_at' => $issuedAt->toDateString(),
            'valid_from' => $issuedAt,
            'valid_until' => null,
            'signing_note' => $note,
        ];
        $period = $letterType?->validity_period ?? 'none';
        if ($period !== 'none') {
            $attributes['valid_until'] = match ($period) {
                '1_week' => $issuedAt->copy()->addWeek(), '2_weeks' => $issuedAt->copy()->addWeeks(2), '1_month' => $issuedAt->copy()->addMonth(),
                '3_months' => $issuedAt->copy()->addMonths(3), '6_months' => $issuedAt->copy()->addMonths(6), '1_year' => $issuedAt->copy()->addYear(),
                default => throw new \DomainException('Masa berlaku jenis surat tidak valid.'),
            };
        }

        if (blank($letter->generated_docx_path)) throw new \DomainException('Dokumen DOCX surat belum tersedia untuk ditandatangani.');

        $unsignedPdfPath = $this->docxPdfService->convert((string) $letter->generated_docx_path);
        $signedPdfPath = null;
        try {
            $signedPdfPath = $this->pdfSigningService->sign(
                sourcePdfPath: $unsignedPdfPath,
                certificate: $signerCertificate,
                signerName: (string) ($letter->signer_name ?: $letter->signerUser()->value('name') ?: $signerCertificate->user()->value('name')),
                reason: $note,
            );
            $oldValues = $this->auditValues($letter);
            $letter = DB::transaction(function () use ($letter, $changedBy, $note, $attributes, $signerCertificate, $signedPdfPath, $oldValues): OutgoingLetter {
                $letter = $this->repository->update($letter, [
                    ...$attributes,
                    'signed_pdf_path' => $signedPdfPath,
                    'signature_certificate_id' => $signerCertificate->id,
                    'signature_profile' => 'pades-b-b',
                    'signed_at' => now(),
                ]);
                $this->recordHistory($letter, 'signed', $changedBy, $note);
                $this->recordAudit('outgoing_letter.signed', $letter, $changedBy, $oldValues, $this->auditValues($letter));
                $letter = $this->repository->update($letter, ['status' => OutgoingLetterStatus::ISSUED]);
                $this->recordHistory($letter, 'issued', $changedBy, $note);
                $this->recordAudit('outgoing_letter.issued', $letter, $changedBy, $oldValues, $this->auditValues($letter));
                return $letter;
            });
            return $letter;
        } catch (\Throwable $e) {
            if ($signedPdfPath !== null) Storage::disk('local')->delete($signedPdfPath);
            throw $e;
        }
    }

    private function resolveSignerCertificate(OutgoingLetter $letter): SignerCertificate
    {
        $certificate = null;

        if ($letter->signature_certificate_id !== null) {
            $certificate = SignerCertificate::query()->find($letter->signature_certificate_id);

            if ($certificate && ($certificate->position_id !== $letter->signer_position_id || $certificate->user_id !== $letter->signer_user_id)) {
                throw new \DomainException('Sertifikat TTE tidak sesuai dengan penanda tangan surat.');
            }
        }

        if ($certificate === null) {
            $certificate = SignerCertificate::query()
                ->where('position_id', $letter->signer_position_id)
                ->where('user_id', $letter->signer_user_id)
                ->where('is_active', true)
                ->latest('created_at')
                ->first();
        }

        if (! $certificate || ! $certificate->isUsable()) throw new \DomainException('Sertifikat TTE aktif penanda tangan belum tersedia atau sudah tidak berlaku.');
        return $certificate;
    }

    private function ensureWithdrawalDecider(int $userId): void
    {
        if (! User::query()->findOrFail($userId)->isSuperAdmin()) throw new \DomainException('Hanya Super Admin yang dapat memutuskan penarikan.');
    }

    private function requiredWorkflowNote(?string $note, string $message): string
    {
        $note = trim((string) ($note ?? request()->input('note', '')));
        if ($note === '') throw new \DomainException($message);
        return $note;
    }

    private function ensureMutable(OutgoingLetter $letter): void
    {
        if ($letter->status === OutgoingLetterStatus::ISSUED) throw new \DomainException('Issued letters cannot be restored or modified.');
    }

    private function transition(OutgoingLetter $letter, OutgoingLetterStatus $from, OutgoingLetterStatus $to, int $changedBy, array $attributes, string $auditAction): OutgoingLetter
    {
        if ($letter->status !== $from) throw new \DomainException('Invalid letter status transition.');
        $oldValues = $this->auditValues($letter);
        $letter = $this->repository->update($letter, [...$attributes, 'status' => $to]);
        $this->recordHistory($letter, $to->value, $changedBy);
        $this->recordAudit($auditAction, $letter, $changedBy, $oldValues, $this->auditValues($letter));
        return $letter;
    }

    private function recordHistory(OutgoingLetter $letter, string $action, int $changedBy, ?string $note = null): void
    {
        $this->historyRepository->create($letter, $action, $changedBy, $note);
    }

    private function recordAudit(string $action, OutgoingLetter $letter, ?int $actorId, ?array $oldValues, ?array $newValues): void
    {
        $actor = $actorId ? User::query()->find($actorId) : Auth::user();
        if ($actor) $this->auditLogService->record($action, $actor, $letter, $oldValues, $newValues);
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
