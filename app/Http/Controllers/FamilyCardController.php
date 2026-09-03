<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FamilyCardController extends Controller
{
    public function pdf(Request $request, string $id): Response
    {
        abort_unless($request->user()?->hasPermission('population.view'), 403);

        $family = Family::query()
            ->with([
                'tenant:id,name,head_name,head_title',
                'headCitizen:id,nama_lengkap',
                'activeMembers' => fn ($query) => $query
                    ->orderBy('urutan')
                    ->with('citizen'),
            ])
            ->when(
                ! $request->user()->isSuperAdmin(),
                fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)
            )
            ->findOrFail($id);

        $pdf = Pdf::loadView('population.family-card-pdf', [
            'family' => $family,
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $filename = 'kartu-keluarga-' . str($family->no_kk)->slug() . '.pdf';

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}
