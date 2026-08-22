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
            ->where('status', OutgoingLetterStatus::ISSUED)
            ->first();

        if ($letter === null) {
            return response()->json([
                'verified' => false,
                'message' => 'Dokumen tidak ditemukan atau belum diterbitkan secara resmi.',
            ], 404);
        }

        return response()->json([
            'verified' => true,
            'data' => [
                'number' => $letter->number,
                'type' => $letter->letterType?->name,
                'recipient_name' => $letter->recipient_name,
                'subject' => $letter->subject,
                'issued_at' => $letter->issued_at?->toDateString(),
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
            ->where('status', OutgoingLetterStatus::ISSUED)
            ->first();

        return view('verification.show', [
            'letter' => $letter,
        ]);
    }
}
