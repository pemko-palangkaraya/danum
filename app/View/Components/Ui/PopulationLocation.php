<?php

declare(strict_types=1);

namespace App\View\Components\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class PopulationLocation extends Component
{
    public function __construct(
        public Collection $provinces,
        public Collection $cities,
        public Collection $districts,
        public Collection $villages,
        public array $locks = [],
        public string $province = '',
        public string $city = '',
        public string $district = '',
        public string $provinceModel = 'provinsi',
        public string $cityModel = 'kabupaten_kota',
        public string $districtModel = 'kecamatan',
        public string $villageModel = 'kelurahan',
        public string $postalModel = 'kode_pos',
        public bool $showPostalCode = true,
    ) {}

    public function render(): View
    {
        return view('components.ui.population-location');
    }
}
