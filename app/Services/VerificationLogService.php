<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OutgoingLetter;
use App\Models\VerificationLog;
use Illuminate\Http\Request;

final class VerificationLogService
{
    public function record(
        Request $request,
        string $action,
        string $result,
        ?OutgoingLetter $letter = null,
        ?string $accessMethod = null,
    ): VerificationLog {
        $user = $request->user();
        $classification = $letter?->letterType?->classification?->code;

        return VerificationLog::query()->create([
            'document_id' => $letter?->id,
            'user_id' => $user?->id,
            'user_type' => $user ? 'authenticated' : 'public',
            'action' => $action,
            'result' => $result,
            'classification' => $classification,
            'access_method' => $accessMethod,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
