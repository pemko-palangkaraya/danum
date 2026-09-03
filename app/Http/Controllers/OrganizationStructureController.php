<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizationStructureController extends Controller
{
    public function pdf(Request $request, ?string $tenant = null): Response
    {
        abort_unless($request->user()?->hasPermission('positions.view'), 403);

        $tenantId = $request->user()->isSuperAdmin() ? $tenant : $request->user()->tenant_id;
        abort_unless($tenantId, 422);
        if ($request->user()->isSuperAdmin()) {
            abort_unless(Tenant::query()->whereKey($tenantId)->where('is_active', true)->exists(), 404);
        }

        $tenantModel = Tenant::query()->findOrFail($tenantId);
        $categoryId = $tenantModel->tenant_category_id;
        $positions = Position::query()
            ->with([
                'holders' => fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('ended_at')->where('started_at', '<=', now())->with('user'),
            ])
            ->where('tenant_category_id', $categoryId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $structures = $tenantModel->positionStructures()->get()->keyBy('position_id');
        $nodes = $positions->map(fn (Position $position) => [
            'position' => $position,
            'structure' => $structures->get($position->id),
        ]);
        $roots = $nodes->filter(fn ($node) => $node['structure']?->is_root)->values();
        if ($roots->isEmpty()) {
            $roots = $nodes->filter(fn ($node) => $node['structure']?->parent_position_id === null)->values();
        }

        $pdf = Pdf::loadView('organization-structure-pdf', [
            'tenant' => $tenantModel,
            'roots' => $roots,
            'nodes' => $nodes,
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $filename = 'struktur-organisasi-' . str($tenantModel->name)->slug() . '.pdf';
        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
