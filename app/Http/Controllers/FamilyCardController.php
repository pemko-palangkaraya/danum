<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use App\Services\PopulationReferenceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FamilyCardController extends Controller
{
    public function pdf(Request $request, string $id, PopulationReferenceService $references): Response
    {
        abort_unless($request->user()?->hasPermission('population.view'), 403);

        $family = Family::query()
            ->with([
                'tenant:id,name,head_name,head_title,city',
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
            'referenceLabels' => [
                'gender' => $references->labels('gender'),
                'blood_type' => $references->labels('blood_type'),
                'religion' => $references->labels('religion'),
                'marital_status' => $references->labels('marital_status'),
                'family_relationship' => $references->labels('family_relationship'),
                'citizenship' => $references->labels('citizenship'),
            ],
        ])->setPaper('a4', 'landscape');

        $filename = 'kartu-keluarga-' . str($family->no_kk)->slug() . '.pdf';

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}
