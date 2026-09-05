<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Citizen;
use App\Models\Tenant;
use Carbon\Carbon;
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

    private const BIRTH_PLACES = [
        'Palangka Raya', 'Sampit', 'Banjarmasin', 'Pangkalan Bun',
        'Kuala Kapuas', 'Muara Teweh', 'Kasongan',
    ];

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female']);
        $birthDate = $this->faker->dateTimeBetween('-80 years', '-1 year');

        return [
            'tenant_id' => Tenant::factory(),
            'nik' => $this->nikFor($birthDate, $gender),
            'nama_lengkap' => $this->randomName($gender),
            'tempat_lahir' => $this->faker->randomElement(self::BIRTH_PLACES),
            'tanggal_lahir' => $birthDate->format('Y-m-d'),
            'jenis_kelamin' => $gender,
            'golongan_darah' => $this->faker->randomElement(['A', 'B', 'AB', 'O', 'unknown']),
            'agama' => $this->faker->randomElement(['islam', 'christian', 'catholic', 'hindu', 'buddhist', 'confucian']),
            'status_perkawinan' => 'single',
            'pendidikan' => 'Tidak/Belum Sekolah',
            'pekerjaan' => 'Tidak/Belum Bekerja',
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

    public function configure(): static
    {
        return $this->afterMaking(function (Citizen $citizen): void {
            $gender = $citizen->jenis_kelamin;
            $birthDate = $citizen->tanggal_lahir instanceof \DateTimeInterface
                ? $citizen->tanggal_lahir
                : Carbon::parse($citizen->tanggal_lahir);
            $age = Carbon::instance($birthDate)->age;

            [$education, $job, $maritalStatus] = $this->profileForAge($age, $gender);

            $citizen->forceFill([
                'nik' => $this->nikFor($birthDate, $gender),
                'pendidikan' => $education,
                'pekerjaan' => $job,
                'status_perkawinan' => $maritalStatus,
            ]);
        });
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

    private function nikFor(\DateTimeInterface $birthDate, string $gender): string
    {
        $date = Carbon::instance($birthDate);
        $day = $date->day + ($gender === 'female' ? 40 : 0);
        $datePart = str_pad((string) $day, 2, '0', STR_PAD_LEFT).$date->format('my');
        $serial = str_pad((string) $this->faker->numberBetween(0, 9999), 4, '0', STR_PAD_LEFT);

        return '627101'.$datePart.$serial;
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
            return [$this->faker->randomElement(['SMA', 'SMP']), 'Pelajar/Mahasiswa', 'single'];
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
