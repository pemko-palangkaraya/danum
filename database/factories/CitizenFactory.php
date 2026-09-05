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

    private const MALE_NAMES = [
        'Agus', 'Andi', 'Bagus', 'Bayu', 'Budi', 'Dedi', 'Eko', 'Fajar',
        'Galih', 'Hendra', 'Joko', 'Rizky', 'Taufik', 'Wahyu', 'Yusuf',
    ];

    private const FEMALE_NAMES = [
        'Aisyah', 'Citra', 'Dewi', 'Fitri', 'Indah', 'Intan', 'Lestari',
        'Maria', 'Maya', 'Putri', 'Rina', 'Sari', 'Siti', 'Vina', 'Yuni',
    ];

    private const LAST_NAMES = [
        'Santoso', 'Saputra', 'Pratama', 'Lestari', 'Wijaya', 'Hidayat',
        'Kurniawan', 'Permata', 'Siregar', 'Setiawan', 'Nugroho', 'Ramadhan',
        'Purnama', 'Kusuma',
    ];

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female']);
        $birthDate = $this->faker->dateTimeBetween('-80 years', '-1 year');
        $age = now()->diffInYears($birthDate);

        $givenNames = $gender === 'male' ? self::MALE_NAMES : self::FEMALE_NAMES;
        $name = $this->faker->randomElement($givenNames).' '.$this->faker->randomElement(self::LAST_NAMES);

        [$education, $job, $maritalStatus] = $this->profileForAge($age, $gender);

        return [
            'tenant_id' => Tenant::factory(),
            // Valid 16-digit Indonesian NIK-shaped demo value. Real regional NIKs
            // should come from authoritative population data, not generated data.
            'nik' => $this->faker->unique()->numerify('62##############'),
            'nama_lengkap' => $name,
            'tempat_lahir' => $this->faker->randomElement([
                'Palangka Raya', 'Sampit', 'Banjarmasin', 'Pangkalan Bun',
                'Kuala Kapuas', 'Muara Teweh', 'Kasongan',
            ]),
            'tanggal_lahir' => $birthDate->format('Y-m-d'),
            'jenis_kelamin' => $gender,
            'golongan_darah' => $this->faker->randomElement(['A', 'B', 'AB', 'O', 'unknown']),
            'agama' => $this->faker->randomElement(['islam', 'christian', 'catholic', 'hindu', 'buddhist', 'confucian']),
            'status_perkawinan' => $maritalStatus,
            'pendidikan' => $education,
            'pekerjaan' => $job,
            'kewarganegaraan' => 'WNI',
            'no_passport' => null,
            'no_kitap' => null,
            'nama_ayah' => $this->randomName('male'),
            'nik_ayah' => null,
            'nama_ibu' => $this->randomName('female'),
            'nik_ibu' => null,
            'status_kependudukan' => 'active',
        ];
    }

    public function forTenant(Tenant|string $tenant): static
    {
        return $this->state([
            'tenant_id' => $tenant instanceof Tenant ? $tenant->getKey() : $tenant,
        ]);
    }

    public function male(): static
    {
        return $this->state(fn (): array => [
            'jenis_kelamin' => 'male',
            'nama_lengkap' => $this->randomName('male'),
        ]);
    }

    public function female(): static
    {
        return $this->state(fn (): array => [
            'jenis_kelamin' => 'female',
            'nama_lengkap' => $this->randomName('female'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['status_kependudukan' => 'inactive']);
    }

    private function randomName(string $gender): string
    {
        $givenNames = $gender === 'male' ? self::MALE_NAMES : self::FEMALE_NAMES;

        return $this->faker->randomElement($givenNames).' '.$this->faker->randomElement(self::LAST_NAMES);
    }

    private function profileForAge(int $age, string $gender): array
    {
        if ($age < 6) {
            return ['Tidak/Belum Sekolah', 'Tidak/Belum Bekerja', 'single'];
        }

        if ($age < 13) {
            return ['SD', 'Pelajar/Mahasiswa', 'single'];
        }

        if ($age < 16) {
            return ['SMP', 'Pelajar/Mahasiswa', 'single'];
        }

        if ($age < 19) {
            return [$this->faker->randomElement(['SMP', 'SMA']), 'Pelajar/Mahasiswa', 'single'];
        }

        if ($age < 22) {
            return [
                $this->faker->randomElement(['SMA', 'Diploma', 'Sarjana']),
                $this->faker->randomElement(['Pelajar/Mahasiswa', 'Karyawan Swasta', 'Wiraswasta', 'Tidak/Belum Bekerja']),
                'single',
            ];
        }

        if ($age < 60) {
            return [
                $this->faker->randomElement(['SMA', 'Diploma', 'Sarjana']),
                $gender === 'female' && $this->faker->boolean(25)
                    ? 'Ibu Rumah Tangga'
                    : $this->faker->randomElement([
                        'Pegawai Negeri Sipil', 'Karyawan Swasta', 'Wiraswasta', 'Guru',
                        'Petani', 'Pedagang', 'Perawat', 'Buruh Harian Lepas', 'Pengemudi',
                        'Ibu Rumah Tangga',
                    ]),
                $this->faker->randomElement(['single', 'married', 'married', 'married', 'divorced', 'widowed']),
            ];
        }

        return [
            $this->faker->randomElement(['SD', 'SMP', 'SMA', 'Diploma', 'Sarjana']),
            $this->faker->randomElement(['Pensiunan', 'Wiraswasta', 'Petani', 'Tidak/Belum Bekerja']),
            $this->faker->randomElement(['married', 'married', 'widowed', 'divorced']),
        ];
    }
}
