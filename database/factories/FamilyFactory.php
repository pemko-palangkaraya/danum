<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Family;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Family> */
class FamilyFactory extends Factory
{
    protected $model = Family::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'no_kk' => $this->faker->unique()->numerify('62##############'),
            'head_citizen_id' => null,
            'alamat' => $this->faker->streetAddress(),
            'rt' => $this->faker->numerify('###'),
            'rw' => $this->faker->numerify('###'),
            'kelurahan' => $this->faker->citySuffix(),
            'kecamatan' => $this->faker->citySuffix(),
            'kabupaten_kota' => 'Palangka Raya',
            'provinsi' => 'Kalimantan Tengah',
            'kode_pos' => $this->faker->numerify('73###'),
            'status' => 'active',
        ];
    }
}
