<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\SignerCertificate;
use App\Models\User;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use App\Repositories\Contracts\OutgoingLetterStatusHistoryRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OutgoingLetterIssuanceService
{
    public function __construct(
        private readonly OutgoingLetterRepositoryInterface $repository,
        private readonly OutgoingLetterStatusHistoryRepositoryInterface $historyRepository,
        private readonly AuditLogService $auditLogService,
        private readonly DocxPdfService $docxPdfService,
        private readonly DocxTteService $docxTteService,
        private readonly PdfSigningService $pdfSigningService,
        private readonly SignerPinService $signerPinService,
    ) {}

    public function issue(OutgoingLetter $letter, int $changedBy, ?string $note = null, ?string $pin = null, bool $signWithTte = true, ?string $issuanceMarker = null): OutgoingLetter
    {
        $note = trim((string) ($note ?? request()->input('note', '')));
        if ($note === '') throw new \DomainException('Catatan penandatanganan wajib diisi.');
        if ($letter->status !== OutgoingLetterStatus::VALIDATED) throw new \DomainException('Hanya surat yang sudah divalidasi yang dapat diterbitkan.');
        if ($letter->signer_user_id !== $changedBy) throw new \DomainException('Hanya penanda tangan yang ditentukan untuk surat ini yang dapat menerbitkan surat.');

        $signer = User::query()->findOrFail($changedBy);
        if (blank($letter->generated_docx_path)) throw new \DomainException('Dokumen DOCX surat belum tersedia untuk diterbitkan.');
        if (! Storage::disk('local')->exists($letter->generated_docx_path)) throw new \DomainException('Dokumen DOCX surat tidak ditemukan. Buat ulang draft surat terlebih dahulu.');

        if ($signWithTte) {
            if (blank($pin)) throw new \DomainException('PIN penandatangan wajib diisi.');
            $this->signerPinService->verify($signer, $pin);
        }

        $letterType = $letter->letterType()->first();
        $issuedAt = now();
        $attributes = [
            'issued_at' => $issuedAt->toDateString(),
            'valid_from' => $issuedAt,
            'valid_until' => null,
            'signing_note' => $note,
            'status' => OutgoingLetterStatus::ISSUED,
            'signed_pdf_path' => null,
            'signature_certificate_id' => null,
            'signature_profile' => null,
            'signed_at' => null,
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

        $sourceDocxPath = Storage::disk('local')->path($letter->generated_docx_path);
        $verificationUrl = url('/verify/' . $letter->verification_token);
        $marker = $issuanceMarker ?? ($signWithTte ? 'tte' : 'qr');
        if (! in_array($marker, ['qr', 'tte'], true)) throw new \DomainException('Marker penerbitan surat tidak valid.');
        $temporaryDocx = null;
        $unsignedPdfPath = null;
        $signedPdfPath = null;

        try {
            $temporaryDocx = $this->docxTteService->createIssuedCopy(
                $sourceDocxPath,
                $verificationUrl,
                $marker,
            );
            $unsignedPdfPath = $this->docxPdfService->convert($temporaryDocx);

            if ($signWithTte) {
                $signerCertificate = $this->resolveSignerCertificate($letter);
                $signedPdfPath = $this->pdfSigningService->sign(
                    sourcePdfPath: Storage::disk('local')->path($unsignedPdfPath),
                    certificate: $signerCertificate,
                    signerName: (string) ($letter->signer_name ?: $letter->signerUser()->value('name') ?: $signerCertificate->user()->value('name')),
                    reason: $note,
                );

                $attributes = [
                    ...$attributes,
                    'unsigned_pdf_path' => $unsignedPdfPath,
                    'signed_pdf_path' => $signedPdfPath,
                    'signature_certificate_id' => $signerCertificate->id,
                    'signature_profile' => 'pades-b-b',
                    'signed_at' => now(),
                ];
            } else {
                $attributes['unsigned_pdf_path'] = $unsignedPdfPath;
            }

            $oldValues = $this->auditValues($letter);
            $letter = DB::transaction(function () use ($letter, $changedBy, $note, $attributes, $oldValues, $signWithTte): OutgoingLetter {
                $letter = $this->repository->update($letter, $attributes);
                $this->recordHistory($letter, 'issued', $changedBy, $note);
                $this->recordAudit('outgoing_letter.issued', $letter, $changedBy, $oldValues, $this->auditValues($letter));

                if ($signWithTte) {
                    $this->recordHistory($letter, 'signed', $changedBy, $note);
                    $this->recordAudit('outgoing_letter.signed', $letter, $changedBy, $oldValues, $this->auditValues($letter));
                }

                return $letter;
            });

            return $letter;
        } catch (\Throwable $e) {
            if ($unsignedPdfPath !== null) Storage::disk('local')->delete($unsignedPdfPath);
            if ($signedPdfPath !== null) Storage::disk('local')->delete($signedPdfPath);
            throw $e;
        } finally {
            if ($temporaryDocx !== null) @unlink($temporaryDocx);
        }
    }

    public function signIssued(OutgoingLetter $letter, int $changedBy, string $pin, ?string $note = null): OutgoingLetter
    {
        $note = trim((string) ($note ?? $letter->signing_note ?? ''));
        if ($note === '') throw new \DomainException('Catatan penandatanganan wajib diisi.');
        if ($letter->status !== OutgoingLetterStatus::ISSUED) throw new \DomainException('Hanya surat yang sudah diterbitkan yang dapat ditandatangani secara elektronik.');
        if ($letter->signer_user_id !== $changedBy) throw new \DomainException('Hanya penanda tangan yang ditentukan untuk surat ini yang dapat menandatangani surat.');
        if (blank($pin)) throw new \DomainException('PIN penandatangan wajib diisi.');
        if (blank($letter->unsigned_pdf_path) || ! Storage::disk('local')->exists($letter->unsigned_pdf_path)) throw new \DomainException('PDF final surat belum tersedia untuk TTE.');
        if (filled($letter->signed_pdf_path) && Storage::disk('local')->exists($letter->signed_pdf_path)) throw new \DomainException('Surat ini sudah memiliki tanda tangan elektronik.');

        $signer = User::query()->findOrFail($changedBy);
        $this->signerPinService->verify($signer, $pin);
        $certificate = $this->resolveSignerCertificate($letter);
        $signedPdfPath = null;

        try {
            $signedPdfPath = $this->pdfSigningService->sign(
                sourcePdfPath: Storage::disk('local')->path($letter->unsigned_pdf_path),
                certificate: $certificate,
                signerName: (string) ($letter->signer_name ?: $letter->signerUser()->value('name') ?: $certificate->user()->value('name')),
                reason: $note,
            );

            $oldValues = $this->auditValues($letter);
            return DB::transaction(function () use ($letter, $changedBy, $note, $certificate, $signedPdfPath, $oldValues): OutgoingLetter {
                $updated = $this->repository->update($letter, [
                    'signed_pdf_path' => $signedPdfPath,
                    'signature_certificate_id' => $certificate->id,
                    'signature_profile' => 'pades-b-b',
                    'signed_at' => now(),
                    'signing_note' => $note,
                ]);
                $this->recordHistory($updated, 'signed', $changedBy, $note);
                $this->recordAudit('outgoing_letter.signed', $updated, $changedBy, $oldValues, $this->auditValues($updated));
                return $updated;
            });
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
            if ($certificate && ($certificate->position_id !== $letter->signer_position_id || $certificate->user_id !== $letter->signer_user_id)) throw new \DomainException('Sertifikat TTE tidak sesuai dengan penanda tangan surat.');
        }
        if ($certificate === null || ! $certificate->isUsable()) {
            $certificate = SignerCertificate::query()
                ->where('position_id', $letter->signer_position_id)
                ->where('user_id', $letter->signer_user_id)
                ->where('is_active', true)
                ->whereNull('revoked_at')
                ->where('valid_from', '<=', now())
                ->where('valid_until', '>', now())
                ->latest('created_at')
                ->first();
        }
        if (! $certificate || ! $certificate->isUsable()) throw new \DomainException('Sertifikat TTE aktif penanda tangan belum tersedia atau sudah tidak berlaku.');
        return $certificate;
    }

    private function recordHistory(OutgoingLetter $letter, string $action, int $changedBy, ?string $note = null): void
    {
        $this->historyRepository->create(['outgoing_letter_id' => $letter->id, 'changed_by' => $changedBy, 'status' => $letter->status, 'action' => $action, 'note' => $note]);
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
            'unsigned_pdf_path' => $letter->unsigned_pdf_path,
            'signed_pdf_path' => $letter->signed_pdf_path,
            'signature_certificate_id' => $letter->signature_certificate_id,
            'signature_profile' => $letter->signature_profile,
            'signed_at' => $letter->signed_at?->toIso8601String(),
        ];
    }
}
