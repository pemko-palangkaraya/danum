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

        $citizens = Citizen::query()->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId));
        $families = Family::query()->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId));

        $gender = (clone $citizens)
            ->select('jenis_kelamin', DB::raw('count(*) as total'))
            ->groupBy('jenis_kelamin')
            ->pluck('total', 'jenis_kelamin');

        $marital = (clone $citizens)
            ->select('status_perkawinan', DB::raw('count(*) as total'))
            ->groupBy('status_perkawinan')
            ->pluck('total', 'status_perkawinan');

        $ageGroups = collect([
            '0–5' => [0, 5], '6–12' => [6, 12], '13–17' => [13, 17],
            '18–25' => [18, 25], '26–35' => [26, 35], '36–45' => [36, 45],
            '46–55' => [46, 55], '56–65' => [56, 65], '>65' => [66, 200],
        ])->mapWithKeys(function (array $range, string $label) use ($citizens) {
            $from = now()->subYears($range[1])->startOfDay();
            $to = now()->subYears($range[0])->endOfDay();

            return [$label => (clone $citizens)->whereBetween('tanggal_lahir', [$from, $to])->count()];
        });

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
}
