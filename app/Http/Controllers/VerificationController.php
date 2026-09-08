<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OutgoingLetterStatus;
use App\Enums\VerificationAccessLevel;
use App\Models\OutgoingLetter;
use App\Services\VerificationLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function __construct(
        private readonly VerificationLogService $verificationLogService,
    ) {}

    private function findLetter(string $token): ?OutgoingLetter
    {
        return OutgoingLetter::query()
            ->with(['tenant:id,name,city', 'letterType:id,name,has_expiry,letter_classification_id', 'letterType.classification:id,code,name,verification_access_level', 'withdrawalRequests.decidedBy'])
            ->where('verification_token', $token)
            ->whereIn('status', [OutgoingLetterStatus::ISSUED, OutgoingLetterStatus::WITHDRAWN])
            ->first();
    }

    public function show(Request $request, string $token): JsonResponse
    {
        $letter = $this->findLetter($token);

        if ($letter === null) {
            $this->verificationLogService->record($request, 'VERIFY', 'NOT_FOUND', null, 'qr');

            return response()->json([
                'verified' => false,
                'message' => 'Dokumen tidak ditemukan atau belum diterbitkan secara resmi.',
            ], 404);
        }

        $level = $this->accessLevel($letter);
        $this->verificationLogService->record($request, 'VERIFY', 'SUCCESS', $letter, 'qr');

        return response()->json([
            'verified' => true,
            'data' => $this->verificationData($letter, $level),
        ]);
    }

    public function page(Request $request, string $token)
    {
        $letter = $this->findLetter($token);

        if ($letter === null) {
            $this->verificationLogService->record($request, 'VERIFY', 'NOT_FOUND', null, 'qr');
            abort(404);
        }

        $level = $this->accessLevel($letter);
        $this->verificationLogService->record($request, 'VERIFY', 'SUCCESS', $letter, 'qr');

        return view('verification.show', [
            'letter' => $letter,
            'accessLevel' => $level,
        ]);
    }

    public function document(Request $request, string $token)
    {
        $letter = $this->findLetter($token);

        if ($letter === null) {
            $this->verificationLogService->record($request, 'DOWNLOAD', 'NOT_FOUND', null, 'verification');
            abort(404);
        }

        $level = $this->accessLevel($letter);
        if ($level === VerificationAccessLevel::RESTRICTED) {
            $this->verificationLogService->record($request, 'DOWNLOAD', 'FORBIDDEN', $letter, 'verification');
            abort(403, 'Dokumen tidak tersedia melalui layanan verifikasi publik.');
        }

        if ($level === VerificationAccessLevel::PROTECTED) {
            if (! Auth::check()) {
                $this->verificationLogService->record($request, 'DOWNLOAD', 'UNAUTHENTICATED', $letter, 'verification');
                return redirect()->guest(route('login'));
            }

            $this->authorize('view', $letter);
        }

        $path = $letter->signed_pdf_path ?: $letter->unsigned_pdf_path;
        if (blank($path) || ! Storage::disk('local')->exists($path)) {
            $this->verificationLogService->record($request, 'DOWNLOAD', 'NOT_AVAILABLE', $letter, 'verification');
            abort(404, 'Dokumen belum tersedia.');
        }

        $this->verificationLogService->record($request, 'DOWNLOAD', 'SUCCESS', $letter, 'verification');

        return Storage::disk('local')->download($path, $letter->number . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function accessLevel(OutgoingLetter $letter): VerificationAccessLevel
    {
        return $letter->letterType?->classification?->verification_access_level
            ?? VerificationAccessLevel::PUBLIC;
    }

    private function verificationData(OutgoingLetter $letter, VerificationAccessLevel $level): array
    {
        $withdrawal = $letter->withdrawalRequests
            ->first(fn ($request) => $request->status->value !== 'pending');
        $state = $letter->status === OutgoingLetterStatus::WITHDRAWN
            ? 'withdrawn'
            : ($letter->isExpired() ? 'expired' : ($letter->isActive() ? 'active' : 'not_yet_active'));

        $data = [
            'number' => $letter->number,
            'type' => $letter->letterType?->name,
            'issued_at' => $letter->issued_at?->toDateString(),
            'signer_name' => $letter->signer_name,
            'signer_title' => $letter->signer_title,
            'signed_at' => $letter->signed_at?->toIso8601String(),
            'tte_status' => filled($letter->signed_pdf_path) ? 'valid' : 'not_signed',
            'state' => $state,
            'tenant' => $letter->tenant?->name,
            'city' => $letter->tenant?->city,
            'access_level' => $level->value,
            'document_id' => $letter->id,
            'document_hash' => $letter->document_hash,
            'document_hash_algorithm' => $letter->document_hash_algorithm,
            'document_url' => $level === VerificationAccessLevel::RESTRICTED ? null : route('verification.document', $letter->verification_token),
        ];

        if ($level === VerificationAccessLevel::PUBLIC) {
            $data['subject'] = $letter->subject;
            $data['recipient_name'] = $letter->recipient_name;
        }

        if ($letter->letterType?->has_expiry) {
            $data['valid_from'] = $letter->valid_from?->toIso8601String();
            $data['valid_until'] = $letter->valid_until?->toIso8601String();
        }

        if ($state === 'withdrawn') {
            $data['withdrawn_at'] = $withdrawal?->decided_at?->toIso8601String();
            $data['withdrawal_note'] = $withdrawal?->decision_note;
        }

        return $data;
    }
}
