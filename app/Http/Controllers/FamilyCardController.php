<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\GenerateFamilyCardsPdf;
use App\Models\Family;
use App\Models\FamilyCardExport;
use App\Models\Tenant;
use App\Services\PopulationReferenceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function pdfAll(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->hasPermission('population.view'), 403);

        $tenantId = $user->isSuperAdmin()
            ? (string) $request->query('tenant_id', '')
            : (string) $user->tenant_id;

        abort_unless($tenantId !== '', 422, 'Tenant harus ditentukan.');

        $tenant = Tenant::query()->findOrFail($tenantId);
        $export = FamilyCardExport::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => 'queued',
        ]);

        GenerateFamilyCardsPdf::dispatch($export->id)->onConnection('database');

        return response()->view('population.family-cards-export-processing', [
            'export' => $export,
            'tenant' => $tenant,
        ]);
    }

    public function exportStatus(Request $request, string $id): Response
    {
        $export = $this->ownedExport($request, $id);
        $disk = Storage::disk('local');
        $ready = $export->status === 'completed'
            && $export->path !== null
            && $disk->exists($export->path);

        return response()->json([
            'status' => $export->status,
            'ready' => $ready,
            'download_url' => $ready
                ? route('population.families.pdf.all.download', ['id' => $export->id])
                : null,
            'error' => $export->status === 'failed'
                ? 'Pembuatan PDF gagal. Silakan coba lagi.'
                : null,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function downloadExport(Request $request, string $id): Response
    {
        $export = $this->ownedExport($request, $id);
        abort_unless($export->status === 'completed', 409, 'PDF belum siap diunduh.');
        abort_unless($export->path !== null, 404, 'File PDF belum tercatat.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($export->path), 404, 'File PDF tidak ditemukan.');

        $stream = $disk->readStream($export->path);
        abort_unless(is_resource($stream), 500, 'File PDF tidak dapat dibaca.');

        return response()->streamDownload(
            function () use ($stream): void {
                fpassthru($stream);
                fclose($stream);
            },
            'kartu-keluarga-semua-' . str($export->tenant->code)->slug() . '.pdf',
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'no-store',
            ],
        );
    }

    private function ownedExport(Request $request, string $id): FamilyCardExport
    {
        return FamilyCardExport::query()
            ->with('tenant:id,code')
            ->whereKey($id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
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
