<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PopulationStatisticsService
{
    public function __construct(private readonly PopulationReferenceService $references) {}

    public function tenants(): Collection
    {
        return Tenant::query()->orderBy('name')->get(['id', 'name', 'code']);
    }

    public function summarize(?string $tenantId): array
    {
        $citizens = Citizen::query()->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId));
        $livingCitizens = (clone $citizens)->where('status_kependudukan', '!=', 'meninggal');
        $families = Family::query()->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId));
        $currentYear = Carbon::now()->year;

        $totalCitizens = (clone $livingCitizens)->count();
        $gender = $this->gender($livingCitizens);
        $ageGroups = $this->buildAgeGroups($livingCitizens);
        $classifiedAge = (int) $ageGroups->sum('total');

        $marital = (clone $livingCitizens)
            ->select('status_perkawinan', DB::raw('count(*) as total'))
            ->groupBy('status_perkawinan')
            ->pluck('total', 'status_perkawinan')
            ->mapWithKeys(fn ($total, $code) => [
                $this->references->label('marital_status', $code, (string) $code) => (int) $total,
            ]);

        return [
            'totalCitizens' => $totalCitizens,
            'totalFamilies' => (clone $families)->count(),
            'activeCitizens' => $totalCitizens,
            'inactiveCitizens' => (clone $citizens)->where('status_kependudukan', '!=', 'active')->count(),
            'deceasedCitizens' => (clone $citizens)->where('status_kependudukan', 'meninggal')->count(),
            'deceasedThisYear' => (clone $citizens)->whereDate('tanggal_meninggal', '>=', $currentYear.'-01-01')->whereDate('tanggal_meninggal', '<=', $currentYear.'-12-31')->count(),
            'birthsThisYear' => (clone $citizens)->whereDate('tanggal_lahir', '>=', $currentYear.'-01-01')->whereDate('tanggal_lahir', '<=', $currentYear.'-12-31')->count(),
            'currentYear' => $currentYear,
            'male' => $this->genderCount($gender, 'male'),
            'female' => $this->genderCount($gender, 'female'),
            'gender' => $gender,
            'marital' => $marital,
            'occupations' => (clone $livingCitizens)->select('pekerjaan', DB::raw('count(*) as total'))->whereNotNull('pekerjaan')->whereRaw("TRIM(pekerjaan) <> ''")->groupBy('pekerjaan')->orderByDesc('total')->limit(8)->pluck('total', 'pekerjaan'),
            'ageGroups' => $ageGroups,
            'toddlers' => $this->countAgeRange($livingCitizens, 0, 5),
            'children' => $this->countAgeRange($livingCitizens, 6, 14),
            'productiveAge' => $this->countAgeRange($livingCitizens, 15, 64),
            'elderly' => $this->countAgeRange($livingCitizens, 65, null),
            'classifiedAge' => $classifiedAge,
            'unclassifiedAge' => max(0, $totalCitizens - $classifiedAge),
        ];
    }

    private function gender($citizens): Collection
    {
        return (clone $citizens)->select('jenis_kelamin', DB::raw('count(*) as total'))->groupBy('jenis_kelamin')->pluck('total', 'jenis_kelamin');
    }

    private function genderCount(Collection $gender, string $wanted): int
    {
        $labels = $wanted === 'male'
            ? ['L', 'LAKI-LAKI', 'LAKI LAKI', 'MALE']
            : ['P', 'PEREMPUAN', 'FEMALE'];

        return (int) $gender->reduce(
            function (int $total, $count, $label) use ($labels): int {
                return $total + (in_array(strtoupper(trim((string) $label)), $labels, true) ? (int) $count : 0);
            },
            0
        );
    }

    private function countAgeRange($citizens, int $min, ?int $max): int
    {
        $query = (clone $citizens)->whereNotNull('tanggal_lahir');

        if ($max === null) {
            $query->whereRaw("DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) >= ?", [$min]);
        } else {
            $query->whereRaw("DATE_PART('year', AGE(CURRENT_DATE, tanggal_lahir)) BETWEEN ? AND ?", [$min, $max]);
        }

        return $query->count();
    }

    private function buildAgeGroups($citizens): Collection
    {
        $labels = ['80+', '75–79', '70–74', '65–69', '60–64', '55–59', '50–54', '45–49', '40–44', '35–39', '30–34', '25–29', '20–24', '15–19', '10–14', '5–9', '0–4'];
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
            WHEN UPPER(TRIM(jenis_kelamin)) IN ('L', 'LAKI-LAKI', 'LAKI LAKI', 'MALE') THEN 'male'
            WHEN UPPER(TRIM(jenis_kelamin)) IN ('P', 'PEREMPUAN', 'FEMALE') THEN 'female'
            ELSE 'other'
        END";

        $rows = (clone $citizens)->whereNotNull('tanggal_lahir')->selectRaw("{$ageSql} AS age_group, {$genderSql} AS gender, COUNT(*) AS total")->groupByRaw("{$ageSql}, {$genderSql}")->get();
        $groups = collect($labels)->mapWithKeys(fn (string $label) => [$label => ['male' => 0, 'female' => 0, 'other' => 0, 'total' => 0]]);

        foreach ($rows as $row) {
            if (! $groups->has($row->age_group)) {
                continue;
            }
            $gender = in_array($row->gender, ['male', 'female', 'other'], true) ? $row->gender : 'other';
            $total = (int) $row->total;
            $group = $groups->get($row->age_group);
            $group[$gender] += $total;
            $group['total'] += $total;
            $groups->put($row->age_group, $group);
        }

        $max = max(1, (int) $groups->max('total'));
        return $groups->map(function (array $group) use ($max): array {
            $group['male_width'] = round(($group['male'] / $max) * 100, 2);
            $group['female_width'] = round(($group['female'] / $max) * 100, 2);
            return $group;
        });
    }
}
