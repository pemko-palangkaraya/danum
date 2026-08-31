<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Citizen;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Citizen> */
class CitizenFactory extends Factory
{
    protected $model = Citizen::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'nik' => $this->faker->unique()->numerify('62##############'),
            'nama_lengkap' => $this->faker->name(),
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'),
            'jenis_kelamin' => $this->faker->randomElement(['male', 'female']),
            'golongan_darah' => $this->faker->randomElement(['A', 'B', 'AB', 'O']),
            'agama' => $this->faker->randomElement(['islam', 'christian', 'catholic', 'hindu', 'buddhist', 'confucian']),
            'status_perkawinan' => $this->faker->randomElement(['single', 'married', 'divorced', 'widowed']),
            'pendidikan' => $this->faker->randomElement(['Tidak/Belum Sekolah', 'SD', 'SMP', 'SMA', 'Diploma', 'Sarjana']),
            'pekerjaan' => $this->faker->jobTitle(),
            'kewarganegaraan' => 'WNI',
            'no_passport' => null,
            'no_kitap' => null,
            'nama_ayah' => $this->faker->name('male'),
            'nik_ayah' => null,
            'nama_ibu' => $this->faker->name('female'),
            'nik_ibu' => null,
            'status_kependudukan' => 'active',
        ];
    }
}
