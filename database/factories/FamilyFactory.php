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

    private const LOCATIONS = [
        ['kelurahan' => 'Langkai', 'kecamatan' => 'Pahandut', 'kode_pos' => '73111'],
        ['kelurahan' => 'Pahandut', 'kecamatan' => 'Pahandut', 'kode_pos' => '73112'],
        ['kelurahan' => 'Panarung', 'kecamatan' => 'Pahandut', 'kode_pos' => '73121'],
        ['kelurahan' => 'Menteng', 'kecamatan' => 'Jekan Raya', 'kode_pos' => '73111'],
        ['kelurahan' => 'Palangka', 'kecamatan' => 'Jekan Raya', 'kode_pos' => '73112'],
        ['kelurahan' => 'Bukit Tunggal', 'kecamatan' => 'Jekan Raya', 'kode_pos' => '73113'],
        ['kelurahan' => 'Kereng Bangkirai', 'kecamatan' => 'Sabangau', 'kode_pos' => '73126'],
        ['kelurahan' => 'Sabaru', 'kecamatan' => 'Sabangau', 'kode_pos' => '73123'],
        ['kelurahan' => 'Tangkiling', 'kecamatan' => 'Bukit Batu', 'kode_pos' => '73127'],
        ['kelurahan' => 'Banturung', 'kecamatan' => 'Bukit Batu', 'kode_pos' => '73113'],
    ];

    private const STREET_NAMES = [
        'Antang Kalang', 'Cilik Riwut', 'Rajawali', 'G. Obos', 'Yos Sudarso',
        'Diponegoro', 'RTA Milono', 'Adonis Samad', 'Tjilik Riwut',
    ];

    public function definition(): array
    {
        $location = $this->faker->randomElement(self::LOCATIONS);

        return [
            'tenant_id' => Tenant::factory(),
            // Demo-only KK number shaped like a Palangka Raya administrative number.
            'no_kk' => $this->faker->unique()->numerify('6271############'),
            'head_citizen_id' => null,
            'alamat' => 'Jl. '.$this->faker->randomElement(self::STREET_NAMES)
                .' No. '.$this->faker->numberBetween(1, 180),
            'rt' => str_pad((string) $this->faker->numberBetween(1, 30), 3, '0', STR_PAD_LEFT),
            'rw' => str_pad((string) $this->faker->numberBetween(1, 15), 3, '0', STR_PAD_LEFT),
            'kelurahan' => $location['kelurahan'],
            'kecamatan' => $location['kecamatan'],
            'kabupaten_kota' => 'Palangka Raya',
            'provinsi' => 'Kalimantan Tengah',
            'kode_pos' => $location['kode_pos'],
            'status' => 'active',
        ];
    }

    public function forTenant(Tenant|string $tenant): static
    {
        return $this->state([
            'tenant_id' => $tenant instanceof Tenant ? $tenant->getKey() : $tenant,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
