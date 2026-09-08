<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Tenant;
use App\Services\FamilyCardsBulkPdfService;
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
            'referenceLabels' => $this->referenceLabels($references),
        ])->setPaper('a4', 'landscape');

        $filename = 'kartu-keluarga-' . str($family->no_kk)->slug() . '.pdf';

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    public function pdfAll(
        Request $request,
        PopulationReferenceService $references,
        FamilyCardsBulkPdfService $bulkPdf,
    ): Response {
        $user = $request->user();
        abort_unless($user?->hasPermission('population.view'), 403);

        $tenantId = $user->isSuperAdmin()
            ? (string) $request->query('tenant_id', '')
            : (string) $user->tenant_id;

        abort_unless($tenantId !== '', 422, 'Tenant harus ditentukan.');

        $tenant = Tenant::query()->findOrFail($tenantId);
        $content = $bulkPdf->generate($tenant, $references);
        $filename = 'kartu-keluarga-semua-' . str($tenant->code)->slug() . '.pdf';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline') . '; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($content),
        ]);
    }

    private function referenceLabels(PopulationReferenceService $references): array
    {
        return [
            'gender' => $references->labels('gender'),
            'blood_type' => $references->labels('blood_type'),
            'religion' => $references->labels('religion'),
            'marital_status' => $references->labels('marital_status'),
            'family_relationship' => $references->labels('family_relationship'),
            'citizenship' => $references->labels('citizenship'),
        ];
    }
}
