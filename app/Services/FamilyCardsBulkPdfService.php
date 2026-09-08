<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

final class FamilyCardsBulkPdfService
{
    private const MEMORY_LIMIT = '512M';
    private const CHUNK_SIZE = 10;

    public function generate(
        Tenant $tenant,
        PopulationReferenceService $references,
    ): string {
        // Bulk PDF tidak dibatasi 30 detik seperti request PHP biasa karena
        // setiap KK harus dirender oleh DomPDF sebelum digabung menjadi satu PDF.
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        // Naikkan limit hanya selama proses bulk PDF; aplikasi lain tetap
        // menggunakan konfigurasi memory_limit normal.
        @ini_set('memory_limit', self::MEMORY_LIMIT);

        $referenceLabels = [
            'gender' => $references->labels('gender'),
            'blood_type' => $references->labels('blood_type'),
            'religion' => $references->labels('religion'),
            'marital_status' => $references->labels('marital_status'),
            'family_relationship' => $references->labels('family_relationship'),
            'citizenship' => $references->labels('citizenship'),
        ];

        $aggregate = $this->aggregate($tenant);
        $output = new Fpdi();

        $summaryPdf = Pdf::loadView('population.family-cards-summary-pdf', [
            'tenant' => $tenant,
            'aggregate' => $aggregate,
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->output();

        $this->appendPdf($output, $summaryPdf);
        unset($summaryPdf);
        gc_collect_cycles();

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
            ->chunk(self::CHUNK_SIZE, function (Collection $families) use ($output, $referenceLabels): void {
                foreach ($families as $family) {
                    $familyPdf = Pdf::loadView('population.family-card-pdf', [
                        'family' => $family,
                        'printedAt' => now(),
                        'referenceLabels' => $referenceLabels,
                    ])->setPaper('a4', 'landscape')->output();

                    $this->appendPdf($output, $familyPdf);
                    unset($familyPdf);
                    gc_collect_cycles();
                }

                unset($families);
                gc_collect_cycles();
            });

        return $output->Output('S');
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

    private function appendPdf(Fpdi $output, string $pdfContent): void
    {
        $pageCount = $output->setSourceFile(StreamReader::createByString($pdfContent));

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $template = $output->importPage($pageNumber);
            $size = $output->getTemplateSize($template);
            $output->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $output->useImportedPage($template);
        }
    }
}
