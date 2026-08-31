<?php

declare(strict_types=1);

namespace App\Livewire\Population;

use App\Models\Citizen;
use App\Models\Family;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Statistics extends Component
{
    public ?string $selectedTenantId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);

        if (! auth()->user()->isSuperAdmin()) {
            $this->selectedTenantId = auth()->user()->tenant_id;
        }
    }

    public function render()
    {
        $tenantId = auth()->user()->isSuperAdmin()
            ? $this->selectedTenantId
            : auth()->user()->tenant_id;

        $citizens = Citizen::query()->when(
            $tenantId,
            fn ($query) => $query->where('tenant_id', $tenantId)
        );
        $families = Family::query()->when(
            $tenantId,
            fn ($query) => $query->where('tenant_id', $tenantId)
        );

        $gender = (clone $citizens)
            ->select('jenis_kelamin', DB::raw('count(*) as total'))
            ->groupBy('jenis_kelamin')
            ->pluck('total', 'jenis_kelamin');

        $marital = (clone $citizens)
            ->select('status_perkawinan', DB::raw('count(*) as total'))
            ->groupBy('status_perkawinan')
            ->pluck('total', 'status_perkawinan');

        $ageGroups = $this->buildAgeGroups($citizens);

        return view('livewire.population.statistics', [
            'totalCitizens' => (clone $citizens)->count(),
            'totalFamilies' => (clone $families)->count(),
            'activeCitizens' => (clone $citizens)->where('status_kependudukan', 'active')->count(),
            'inactiveCitizens' => (clone $citizens)->where('status_kependudukan', '!=', 'active')->count(),
            'male' => $gender->get('L', $gender->get('laki-laki', 0)),
            'female' => $gender->get('P', $gender->get('perempuan', 0)),
            'gender' => $gender,
            'marital' => $marital,
            'ageGroups' => $ageGroups,
        ]);
    }

    private function buildAgeGroups($citizens)
    {
        $labels = [
            '80+', '75–79', '70–74', '65–69', '60–64', '55–59', '50–54',
            '45–49', '40–44', '35–39', '30–34', '25–29', '20–24', '15–19',
            '10–14', '5–9', '0–4',
        ];

        $ageSql = "CASE
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) >= 80 THEN '80+'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 75 AND 79 THEN '75–79'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 70 AND 74 THEN '70–74'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 65 AND 69 THEN '65–69'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 60 AND 64 THEN '60–64'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 55 AND 59 THEN '55–59'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 50 AND 54 THEN '50–54'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 45 AND 49 THEN '45–49'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 40 AND 44 THEN '40–44'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 35 AND 39 THEN '35–39'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 30 AND 34 THEN '30–34'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 25 AND 29 THEN '25–29'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 20 AND 24 THEN '20–24'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 15 AND 19 THEN '15–19'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 10 AND 14 THEN '10–14'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 5 AND 9 THEN '5–9'
            WHEN DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN 0 AND 4 THEN '0–4'
        END";

        $genderSql = "CASE
            WHEN UPPER(TRIM(jenis_kelamin)) IN ('L', 'LAKI-LAKI', 'LAKI LAKI') THEN 'male'
            WHEN UPPER(TRIM(jenis_kelamin)) IN ('P', 'PEREMPUAN') THEN 'female'
            ELSE 'other'
        END";

        $rows = (clone $citizens)
            ->whereNotNull('tanggal_lahir')
            ->selectRaw("{$ageSql} AS age_group, {$genderSql} AS gender, COUNT(*) AS total")
            ->groupByRaw("{$ageSql}, {$genderSql}")
            ->get();

        $groups = collect($labels)->mapWithKeys(fn (string $label) => [
            $label => [
                'male' => 0,
                'female' => 0,
                'other' => 0,
                'total' => 0,
            ],
        ]);

        foreach ($rows as $row) {
            if (! $groups->has($row->age_group)) {
                continue;
            }

            $gender = $row->gender;
            $total = (int) $row->total;
            $groups[$row->age_group][$gender] += $total;
            $groups[$row->age_group]['total'] += $total;
        }

        $max = max(1, (int) $groups->max('total'));

        return $groups->map(function (array $group) use ($max) {
            $group['male_width'] = round(($group['male'] / $max) * 100, 2);
            $group['female_width'] = round(($group['female'] / $max) * 100, 2);

            return $group;
        });
    }
}
