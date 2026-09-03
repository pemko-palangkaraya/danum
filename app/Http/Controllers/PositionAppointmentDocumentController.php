<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PositionHolder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PositionAppointmentDocumentController extends Controller
{
    public function show(PositionHolder $holder): StreamedResponse
    {
        $user = request()->user();
        abort_unless($user?->hasPermission('positions.view'), 403);
        abort_unless($holder->appointment_document_path, 404);

        if (! $user->isSuperAdmin()) {
            abort_unless((string) $holder->tenant_id === (string) $user->tenant_id, 403);
        }

        abort_unless(Storage::disk('local')->exists($holder->appointment_document_path), 404);

        $filename = 'SK-' . ($holder->appointment_number ?: 'pengangkatan') . '.pdf';
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?: 'SK-pengangkatan.pdf';

        return Storage::disk('local')->download($holder->appointment_document_path, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
