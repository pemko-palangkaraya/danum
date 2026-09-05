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
        $gender = $this->faker->randomElement(['male', 'female']);
        $birthDate = $this->faker->dateTimeBetween('-80 years', '-1 year');
        $age = now()->diffInYears($birthDate);

        $firstNames = [
            'Andi', 'Budi', 'Citra', 'Dedi', 'Dewi', 'Fajar', 'Hendra', 'Indah',
            'Lestari', 'Maria', 'Rina', 'Sari', 'Siti', 'Wahyu', 'Yuni', 'Agus',
            'Aisyah', 'Bagus', 'Bayu', 'Dian', 'Eko', 'Fitri', 'Galih', 'Intan',
            'Joko', 'Maya', 'Nanda', 'Putri', 'Rizky', 'Taufik', 'Vina', 'Yusuf',
        ];
        $maleNames = ['Agus', 'Andi', 'Bagus', 'Bayu', 'Budi', 'Dedi', 'Eko', 'Fajar', 'Galih', 'Hendra', 'Joko', 'Rizky', 'Taufik', 'Wahyu', 'Yusuf'];
        $femaleNames = ['Aisyah', 'Citra', 'Dewi', 'Fitri', 'Indah', 'Intan', 'Lestari', 'Maria', 'Maya', 'Putri', 'Rina', 'Sari', 'Siti', 'Vina', 'Yuni'];
        $lastNames = ['Santoso', 'Saputra', 'Pratama', 'Lestari', 'Wijaya', 'Hidayat', 'Kurniawan', 'Permata', 'Siregar', 'Setiawan', 'Nugroho', 'Ramadhan', 'Purnama', 'Kusuma'];
        $cities = ['Palangka Raya', 'Sampit', 'Banjarmasin', 'Pangkalan Bun', 'Kuala Kapuas', 'Muara Teweh', 'Kasongan'];
        $jobs = [
            'Pegawai Negeri Sipil', 'Karyawan Swasta', 'Wiraswasta', 'Guru', 'Petani',
            'Pedagang', 'Perawat', 'Buruh Harian Lepas', 'Pengemudi', 'Ibu Rumah Tangga',
            'Pelajar/Mahasiswa', 'Pensiunan', 'Tidak/Belum Bekerja',
        ];

        $givenNames = $gender === 'male' ? $maleNames : $femaleNames;
        $name = $this->faker->randomElement($givenNames).' '.$this->faker->randomElement($lastNames);

        if ($age < 6) {
            $education = 'Tidak/Belum Sekolah';
            $job = 'Tidak/Belum Bekerja';
            $maritalStatus = 'single';
        } elseif ($age < 13) {
            $education = 'SD';
            $job = 'Pelajar/Mahasiswa';
            $maritalStatus = 'single';
        } elseif ($age < 16) {
            $education = 'SMP';
            $job = 'Pelajar/Mahasiswa';
            $maritalStatus = 'single';
        } elseif ($age < 19) {
            $education = $this->faker->randomElement(['SMP', 'SMA']);
            $job = 'Pelajar/Mahasiswa';
            $maritalStatus = 'single';
        } elseif ($age < 22) {
            $education = $this->faker->randomElement(['SMA', 'Diploma', 'Sarjana']);
            $job = $this->faker->randomElement(['Pelajar/Mahasiswa', 'Karyawan Swasta', 'Wiraswasta', 'Tidak/Belum Bekerja']);
            $maritalStatus = 'single';
        } elseif ($age < 60) {
            $education = $this->faker->randomElement(['SMA', 'Diploma', 'Sarjana']);
            $job = $gender === 'female' && $this->faker->boolean(25)
                ? 'Ibu Rumah Tangga'
                : $this->faker->randomElement($jobs);
            $maritalStatus = $this->faker->randomElement(['single', 'married', 'married', 'married', 'divorced', 'widowed']);
        } else {
            $education = $this->faker->randomElement(['SD', 'SMP', 'SMA', 'Diploma', 'Sarjana']);
            $job = $this->faker->randomElement(['Pensiunan', 'Wiraswasta', 'Petani', 'Tidak/Belum Bekerja']);
            $maritalStatus = $this->faker->randomElement(['married', 'married', 'widowed', 'divorced']);
        }

        return [
            'tenant_id' => Tenant::factory(),
            'nik' => $this->faker->unique()->numerify('62##############'),
            'nama_lengkap' => $name,
            'tempat_lahir' => $this->faker->randomElement($cities),
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
            'nama_ayah' => $this->faker->randomElement($maleNames).' '.$this->faker->randomElement($lastNames),
            'nik_ayah' => null,
            'nama_ibu' => $this->faker->randomElement($femaleNames).' '.$this->faker->randomElement($lastNames),
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
        return $this->state(['jenis_kelamin' => 'male']);
    }

    public function female(): static
    {
        return $this->state(['jenis_kelamin' => 'female']);
    }

    public function inactive(): static
    {
        return $this->state(['status_kependudukan' => 'inactive']);
    }
}
