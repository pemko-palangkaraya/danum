<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use App\Support\Pdf\FileBufferedFpdi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

final class FamilyCardsBulkPdfService
{
    private const MEMORY_LIMIT = '512M';

    private const CHUNK_SIZE = 10;

    public function generate(
        Tenant $tenant,
        PopulationReferenceService $references,
    ): string {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', self::MEMORY_LIMIT);

        $referenceLabels = [
            'gender' => $references->labels('gender'),
            'blood_type' => $references->labels('blood_type'),
            'religion' => $references->labels('religion'),
            'marital_status' => $references->labels('marital_status'),
            'family_relationship' => $references->labels('family_relationship'),
            'citizenship' => $references->labels('citizenship'),
        ];

        $temporaryDirectory = storage_path('app/tmp/family-cards');
        $this->ensureDirectory($temporaryDirectory);

        $outputPath = $temporaryDirectory . '/all-' . $tenant->id . '-' . Str::uuid() . '.pdf';
        $output = new FileBufferedFpdi();
        $output->openFile($outputPath);

        try {
            $summaryPath = $this->renderSummary($tenant, $temporaryDirectory);

            try {
                $this->appendPdf($output, $summaryPath);
            } finally {
                @unlink($summaryPath);
            }

            Family::query()
                ->where('tenant_id', $tenant->id)
                ->with([
                    'tenant:id,name,head_name,head_title,city',
                    'headCitizen:id,nama_lengkap',
                    'activeMembers' => fn ($query) => $query
                        ->orderBy('urutan')
                        ->with('citizen'),
                ])
                ->orderBy('no_kk')
                ->orderBy('id')
                ->chunk(self::CHUNK_SIZE, function (Collection $families) use ($output, $referenceLabels, $temporaryDirectory): void {
                    foreach ($families as $family) {
                        $familyPath = $this->renderFamily($family, $referenceLabels, $temporaryDirectory);

                        try {
                            $this->appendPdf($output, $familyPath);
                        } finally {
                            @unlink($familyPath);
                        }

                        unset($familyPath);
                        gc_collect_cycles();
                    }

                    unset($families);
                    gc_collect_cycles();
                });

            $output->Close();

            return $outputPath;
        } catch (Throwable $exception) {
            @unlink($outputPath);
            throw $exception;
        }
    }

    private function renderSummary(Tenant $tenant, string $temporaryDirectory): string
    {
        $path = $this->temporaryPdfPath($temporaryDirectory, 'summary');

        Pdf::loadView('population.family-cards-summary-pdf', [
            'tenant' => $tenant,
            'aggregate' => $this->aggregate($tenant),
            'printedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->save($path);

        return $path;
    }

    private function renderFamily(Family $family, array $referenceLabels, string $temporaryDirectory): string
    {
        $path = $this->temporaryPdfPath($temporaryDirectory, 'family');

        Pdf::loadView('population.family-card-pdf', [
            'family' => $family,
            'printedAt' => now(),
            'referenceLabels' => $referenceLabels,
        ])
            ->setPaper('a4', 'landscape')
            ->save($path);

        return $path;
    }

    private function temporaryPdfPath(string $directory, string $prefix): string
    {
        return $directory . '/' . $prefix . '-' . Str::uuid() . '.pdf';
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create temporary PDF directory: ' . $directory);
        }
    }

    private function appendPdf(FileBufferedFpdi $output, string $pdfPath): void
    {
        $pageCount = $output->setSourceFile($pdfPath);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $template = $output->importPage($pageNumber);
            $size = $output->getTemplateSize($template);

            $output->AddPage(
                $size['orientation'],
                [$size['width'], $size['height']],
            );
            $output->useImportedPage($template);
        }
    }

    private function aggregate(Tenant $tenant): array
    {
        $familyQuery = Family::query()->where('tenant_id', $tenant->id);
        $totalFamilies = (clone $familyQuery)->count();
        $activeFamilies = (clone $familyQuery)->where('status', 'active')->count();

        $membersQuery = FamilyMember::query()
            ->where('status', 'active')
            ->whereHas('family', fn ($query) => $query->where('tenant_id', $tenant->id));

        $totalMembers = $membersQuery->count();

        return [
            'total_families' => $totalFamilies,
            'active_families' => $activeFamilies,
            'total_members' => $totalMembers,
            'male' => $this->countMembersByGender($tenant, 'male'),
            'female' => $this->countMembersByGender($tenant, 'female'),
            'wni' => $this->countMembersByCitizenship($tenant, 'WNI'),
            'wna' => $this->countMembersByCitizenship($tenant, 'WNA'),
            'average_members' => $totalFamilies > 0
                ? round($totalMembers / $totalFamilies, 2)
                : 0,
        ];
    }

    private function countMembersByGender(Tenant $tenant, string $gender): int
    {
        return FamilyMember::query()
            ->where('status', 'active')
            ->whereHas('family', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereHas('citizen', fn ($query) => $query->where('jenis_kelamin', $gender))
            ->count();
    }

    private function countMembersByCitizenship(Tenant $tenant, string $citizenship): int
    {
        return FamilyMember::query()
            ->where('status', 'active')
            ->whereHas('family', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereHas('citizen', fn ($query) => $query->where('kewarganegaraan', $citizenship))
            ->count();
    }
}
