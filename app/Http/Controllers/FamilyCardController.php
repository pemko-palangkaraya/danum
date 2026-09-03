<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class FamilyCardController extends Controller
{
    public function pdf(Request $request, string $id): Response
    {
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

        $this->authorize('view', $family);

        $pdf = Pdf::loadView('population.family-card-pdf', [
            'family' => $family,
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $filename = 'kartu-keluarga-' . str($family->no_kk)->slug() . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}
