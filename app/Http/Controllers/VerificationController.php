<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    private function findLetter(string $token): ?OutgoingLetter
    {
        return OutgoingLetter::query()
            ->with(['tenant:id,name,city', 'letterType:id,name,has_expiry', 'withdrawalRequests.decidedBy'])
            ->where('verification_token', $token)
            ->whereIn('status', [OutgoingLetterStatus::ISSUED, OutgoingLetterStatus::WITHDRAWN])
            ->first();
    }

    public function show(string $token): JsonResponse
    {
        $letter = $this->findLetter($token);

        if ($letter === null) {
            return response()->json([
                'verified' => false,
                'message' => 'Dokumen tidak ditemukan atau belum diterbitkan secara resmi.',
            ], 404);
        }

        $withdrawal = $letter->withdrawalRequests
            ->first(fn ($request) => $request->status->value !== 'pending');
        $state = $letter->status === OutgoingLetterStatus::WITHDRAWN
            ? 'withdrawn'
            : ($letter->isExpired() ? 'expired' : ($letter->isActive() ? 'active' : 'not_yet_active'));

        return response()->json([
            'verified' => true,
            'data' => [
                'number' => $letter->number,
                'type' => $letter->letterType?->name,
                'recipient_name' => $letter->recipient_name,
                'subject' => $letter->subject,
                'issued_at' => $letter->issued_at?->toDateString(),
                'valid_from' => $letter->letterType?->has_expiry ? $letter->valid_from?->toIso8601String() : null,
                'valid_until' => $letter->letterType?->has_expiry ? $letter->valid_until?->toIso8601String() : null,
                'withdrawn_at' => $state === 'withdrawn' ? $withdrawal?->decided_at?->toIso8601String() : null,
                'withdrawal_note' => $state === 'withdrawn' ? $withdrawal?->decision_note : null,
                'state' => $state,
                'tenant' => $letter->tenant?->name,
                'city' => $letter->tenant?->city,
            ],
        ]);
    }

    public function page(Request $request, string $token)
    {
        $letter = $this->findLetter($token);

        return view('verification.show', [
            'letter' => $letter,
        ]);
    }
}
