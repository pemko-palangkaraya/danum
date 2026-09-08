<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

final class FamilyCardsBulkPdfService
{
    public function generate(
        Tenant $tenant,
        PopulationReferenceService $references,
    ): string {
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
            ->chunk(25, function ($families) use ($output, $referenceLabels): void {
                foreach ($families as $family) {
                    $familyPdf = Pdf::loadView('population.family-card-pdf', [
                        'family' => $family,
                        'printedAt' => now(),
                        'referenceLabels' => $referenceLabels,
                    ])->setPaper('a4', 'landscape')->output();

                    $this->appendPdf($output, $familyPdf);
                    unset($familyPdf);
                }

                unset($families);
            });

        return $output->Output('S');
    }

    private function aggregate(Tenant $tenant): array
    {
        $familyQuery = Family::query()->where('tenant_id', $tenant->id);

        $totalFamilies = (clone $familyQuery)->count();
        $activeFamilies = (clone $familyQuery)->where('status', 'active')->count();

        $membersQuery = $tenant->id
            ? \App\Models\FamilyMember::query()
                ->where('status', 'active')
                ->whereHas('family', fn ($query) => $query->where('tenant_id', $tenant->id))
            : null;

        $totalMembers = $membersQuery?->count() ?? 0;
        $male = $this->countMembersByGender($tenant, 'male');
        $female = $this->countMembersByGender($tenant, 'female');
        $wni = $this->countMembersByCitizenship($tenant, 'WNI');
        $wna = $this->countMembersByCitizenship($tenant, 'WNA');

        return [
            'total_families' => $totalFamilies,
            'active_families' => $activeFamilies,
            'total_members' => $totalMembers,
            'male' => $male,
            'female' => $female,
            'wni' => $wni,
            'wna' => $wna,
            'average_members' => $totalFamilies > 0
                ? round($totalMembers / $totalFamilies, 2)
                : 0,
        ];
    }

    private function countMembersByGender(Tenant $tenant, string $gender): int
    {
        return \App\Models\FamilyMember::query()
            ->where('status', 'active')
            ->whereHas('family', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereHas('citizen', fn ($query) => $query->where('jenis_kelamin', $gender))
            ->count();
    }

    private function countMembersByCitizenship(Tenant $tenant, string $citizenship): int
    {
        return \App\Models\FamilyMember::query()
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
            $output->useImportedPage($template, 0, 0, 0, 0, true);
        }
    }
}
