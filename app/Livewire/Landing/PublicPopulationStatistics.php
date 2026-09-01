<?php

declare(strict_types=1);

namespace App\Livewire\Landing;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PublicPopulationStatistics extends Component
{
    public ?string $province = null;
    public ?string $city = null;
    public ?string $district = null;
    public ?string $village = null;

    public function updatedProvince(): void
    {
        $this->city = null;
        $this->district = null;
        $this->village = null;
    }

    public function updatedCity(): void
    {
        $this->district = null;
        $this->village = null;
    }

    public function updatedDistrict(): void
    {
        $this->village = null;
    }

    public function render()
    {
        $tenants = Tenant::query()->select(['id', 'province', 'city', 'district', 'village']);

        $provinces = Tenant::query()
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->distinct()
            ->orderBy('province')
            ->pluck('province');

        $cities = Tenant::query()
            ->when($this->province, fn ($q) => $q->where('province', $this->province))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $districts = Tenant::query()
            ->when($this->province, fn ($q) => $q->where('province', $this->province))
            ->when($this->city, fn ($q) => $q->where('city', $this->city))
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->distinct()
            ->orderBy('district')
            ->pluck('district');

        $villages = Tenant::query()
            ->when($this->province, fn ($q) => $q->where('province', $this->province))
            ->when($this->city, fn ($q) => $q->where('city', $this->city))
            ->when($this->district, fn ($q) => $q->where('district', $this->district))
            ->whereNotNull('village')
            ->where('village', '!=', '')
            ->distinct()
            ->orderBy('village')
            ->pluck('village');

        $tenants->when($this->province, fn ($q) => $q->where('province', $this->province))
            ->when($this->city, fn ($q) => $q->where('city', $this->city))
            ->when($this->district, fn ($q) => $q->where('district', $this->district))
            ->when($this->village, fn ($q) => $q->where('village', $this->village));

        $tenantIds = $tenants->pluck('id');
        $citizens = Citizen::query()->whereIn('tenant_id', $tenantIds);
        $families = Family::query()->whereIn('tenant_id', $tenantIds);

        $gender = (clone $citizens)
            ->select('jenis_kelamin', DB::raw('count(*) as total'))
            ->groupBy('jenis_kelamin')
            ->pluck('total', 'jenis_kelamin');

        $male = 0;
        $female = 0;
        foreach ($gender as $label => $count) {
            $normalized = strtoupper(trim((string) $label));
            if (in_array($normalized, ['L', 'LAKI-LAKI', 'LAKI LAKI', 'MALE'], true)) {
                $male += (int) $count;
            } elseif (in_array($normalized, ['P', 'PEREMPUAN', 'FEMALE'], true)) {
                $female += (int) $count;
            }
        }

        return view('livewire.landing.public-population-statistics', [
            'provinces' => $provinces,
            'cities' => $cities,
            'districts' => $districts,
            'villages' => $villages,
            'totalCitizens' => (clone $citizens)->count(),
            'totalFamilies' => (clone $families)->count(),
            'male' => $male,
            'female' => $female,
        ]);
    }
}
