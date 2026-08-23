<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $letter = OutgoingLetter::query()
            ->with(['tenant:id,name,city', 'letterType:id,name'])
            ->where('verification_token', $token)
            ->whereIn('status', [OutgoingLetterStatus::ISSUED, OutgoingLetterStatus::WITHDRAWN])
            ->first();

        if ($letter === null) {
            return response()->json([
                'verified' => false,
                'message' => 'Dokumen tidak ditemukan atau belum diterbitkan secara resmi.',
            ], 404);
        }

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
                'valid_from' => $letter->valid_from?->toIso8601String(),
                'valid_until' => $letter->valid_until?->toIso8601String(),
                'state' => $state,
                'tenant' => $letter->tenant?->name,
                'city' => $letter->tenant?->city,
            ],
        ]);
    }

    public function page(Request $request, string $token)
    {
        $letter = OutgoingLetter::query()
            ->with(['tenant:id,name,city', 'letterType:id,name'])
            ->where('verification_token', $token)
            ->whereIn('status', [OutgoingLetterStatus::ISSUED, OutgoingLetterStatus::WITHDRAWN])
            ->first();

        return view('verification.show', [
            'letter' => $letter,
        ]);
    }
}
