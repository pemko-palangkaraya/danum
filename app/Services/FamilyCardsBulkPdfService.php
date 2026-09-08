<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use App\Support\Pdf\FileBufferedFpdi;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class FamilyCardsBulkPdfService
{
    private const MEMORY_LIMIT = '512M';

    private const CHUNK_SIZE = 10;

    private const FAMILY_LIMIT = 50;

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
            $summaryPath = $this->renderSummary($tenant, $temporaryDirectory, $referenceLabels);

            try {
                $this->appendPdf($output, $summaryPath);
            } finally {
                @unlink($summaryPath);
            }

            $this->selectedFamilyQuery($tenant)
                ->with([
                    'tenant:id,name,head_name,head_title,city',
                    'headCitizen:id,nama_lengkap',
                    'activeMembers' => fn ($query) => $query
                        ->orderBy('urutan')
                        ->with('citizen'),
                ])
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

    private function renderSummary(
        Tenant $tenant,
        string $temporaryDirectory,
        array $referenceLabels,
    ): string {
        $path = $this->temporaryPdfPath($temporaryDirectory, 'summary');

        Pdf::loadView('population.family-cards-summary-pdf', [
            'tenant' => $tenant,
            'aggregate' => $this->aggregate($tenant, $referenceLabels),
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

    private function aggregate(Tenant $tenant, array $referenceLabels): array
    {
        $familyQuery = $this->selectedFamilyQuery($tenant);
        $familyIds = (clone $familyQuery)->pluck('id');
        $totalFamilies = $familyIds->count();
        $activeFamilies = Family::query()
            ->whereIn('id', $familyIds)
            ->where('status', 'active')
            ->count();

        $membersQuery = FamilyMember::query()
            ->where('family_members.status', 'active')
            ->whereIn('family_members.family_id', $familyIds);

        $totalMembers = (clone $membersQuery)->count();
        $familySizes = $this->familySizes($familyQuery);
        $memberStats = $this->memberStats($membersQuery, $referenceLabels);
        $locations = $this->locationStats($familyQuery, $membersQuery);

        $sizes = $familySizes->values()->sort()->values();
        $minimumMembers = $sizes->isNotEmpty() ? (int) $sizes->first() : 0;
        $maximumMembers = $sizes->isNotEmpty() ? (int) $sizes->last() : 0;

        return [
            'total_families' => $totalFamilies,
            'active_families' => $activeFamilies,
            'total_members' => $totalMembers,
            'male' => $memberStats['gender']['Laki-laki'] ?? 0,
            'female' => $memberStats['gender']['Perempuan'] ?? 0,
            'gender' => $memberStats['gender'],
            'gender_percentages' => $this->percentages($memberStats['gender'], $totalMembers),
            'age_groups' => $memberStats['age_groups'],
            'marital_status' => $memberStats['marital_status'],
            'relationships' => $memberStats['relationships'],
            'education' => $memberStats['education'],
            'religion' => $memberStats['religion'],
            'occupation' => $memberStats['occupation'],
            'citizenship' => $memberStats['citizenship'],
            'wni' => $memberStats['citizenship']['WNI'] ?? 0,
            'wna' => $memberStats['citizenship']['WNA'] ?? 0,
            'average_members' => $totalFamilies > 0 ? round($totalMembers / $totalFamilies, 2) : 0,
            'median_members' => $this->median($sizes),
            'minimum_members' => $minimumMembers,
            'maximum_members' => $maximumMembers,
            'single_member_families' => $familySizes->filter(fn (int $size): bool => $size === 1)->count(),
            'large_families' => $familySizes->filter(fn (int $size): bool => $size >= 5)->count(),
            'families_with_children' => count($memberStats['families_with_children']),
            'families_with_elderly' => count($memberStats['families_with_elderly']),
            'rt' => $locations['rt'],
            'rt_rw' => $locations['rt_rw'],
            'kelurahan' => $locations['kelurahan'],
        ];
    }

    private function selectedFamilyQuery(Tenant $tenant)
    {
        return Family::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('no_kk')
            ->orderBy('id')
            ->limit(self::FAMILY_LIMIT);
    }

    private function familySizes($familyQuery): Collection
    {
        $counts = FamilyMember::query()
            ->select('family_id', DB::raw('count(*) as total'))
            ->where('status', 'active')
            ->whereIn('family_id', (clone $familyQuery)->select('id'))
            ->groupBy('family_id')
            ->pluck('total', 'family_id')
            ->map(fn ($count): int => (int) $count);

        return (clone $familyQuery)
            ->pluck('id')
            ->map(fn (string $familyId): int => $counts->get($familyId, 0));
    }

    private function memberStats($membersQuery, array $referenceLabels): array
    {
        $stats = [
            'gender' => [],
            'age_groups' => [
                '0–5 tahun' => 0,
                '6–12 tahun' => 0,
                '13–17 tahun' => 0,
                '18–59 tahun' => 0,
                '≥60 tahun' => 0,
                'Tidak tercatat' => 0,
            ],
            'marital_status' => [],
            'relationships' => [],
