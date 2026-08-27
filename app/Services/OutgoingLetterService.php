<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\SignerCertificate;
use App\Models\User;
use App\Repositories\OutgoingLetterRepository;
use App\Repositories\OutgoingLetterStatusHistoryRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OutgoingLetterService
{
    public function __construct(
        private readonly OutgoingLetterRepository $repository,
        private readonly OutgoingLetterStatusHistoryRepository $historyRepository,
        private readonly AuditLogService $auditLogService,
        private readonly DocxPdfService $docxPdfService,
        private readonly PdfSigningService $pdfSigningService,
        private readonly SignerPinService $signerPinService,
    ) {}

    private function resolveSignerCertificate(OutgoingLetter $letter): SignerCertificate
    {
        $certificate = null;

        if ($letter->signature_certificate_id !== null) {
            $certificate = SignerCertificate::query()->find($letter->signature_certificate_id);

            if ($certificate && ($certificate->position_id !== $letter->signer_position_id || $certificate->user_id !== $letter->signer_user_id)) {
                throw new \DomainException('Sertifikat TTE tidak sesuai dengan penanda tangan surat.');
            }
        }

        // Always resolve by the signer identity as a fallback. The certificate ID
        // stored on an older/draft letter can be stale, while the current active
        // certificate for the assigned position/user may still be usable.
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

        if (! $certificate || ! $certificate->isUsable()) {
            throw new \DomainException('Sertifikat TTE aktif penanda tangan belum tersedia atau sudah tidak berlaku.');
        }

        return $certificate;
    }

    private function requiredWorkflowNote(?string $note, string $message): string
    {
        $note = trim((string) ($note ?? request()->input('note', '')));
        if ($note === '') throw new \DomainException($message);
        return $note;
    }
}
