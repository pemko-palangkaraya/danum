<?php

declare(strict_types=1);

namespace App\View\Components\Ui;

use App\Services\PopulationLocationService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Collection;

class PopulationLocation extends Component
{
    public Collection $provinces;
    public Collection $cities;
    public Collection $districts;
    public Collection $villages;

    public function __construct(
        public string $tenantId,
        public string $province = '',
        public string $city = '',
        public string $district = '',
        public string $provinceModel = 'provinsi',
        public string $cityModel = 'kabupaten_kota',
        public string $districtModel = 'kecamatan',
        public string $villageModel = 'kelurahan',
        public string $postalModel = 'kode_pos',
        public bool $showPostalCode = true,
    ) {
        $service = app(PopulationLocationService::class);
        $options = $tenantId !== ''
            ? $service->optionsForTenant($tenantId, $province, $city, $district)
            : $service->emptyOptions();

        $this->provinces = $options['provinces'];
        $this->cities = $options['cities'];
        $this->districts = $options['districts'];
        $this->villages = $options['villages'];
    }

    public function render(): View
    {
        return view('components.ui.population-location');
    }
}
