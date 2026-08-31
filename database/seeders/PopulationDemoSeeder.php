<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PopulationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()
            ->where('code', env('DANUM_DEMO_TENANT_CODE', 'DEMO001'))
            ->first();

        if ($tenant === null) {
            throw new \RuntimeException('Demo tenant tidak ditemukan. Jalankan DatabaseSeeder terlebih dahulu.');
        }

        $this->command?->info("Membuat data kependudukan demo untuk tenant {$tenant->name}...");

        foreach (range(1, 20) as $familyNumber) {
            $head = Citizen::factory()
                ->forTenant($tenant)
                ->male()
                ->state([
                    'tanggal_lahir' => fake()->dateTimeBetween('-65 years', '-30 years')->format('Y-m-d'),
                    'status_perkawinan' => 'married',
                ])
                ->create();

            $family = Family::factory()
                ->forTenant($tenant)
                ->state([
                    'head_citizen_id' => $head->id,
                    'rt' => str_pad((string) fake()->numberBetween(1, 12), 3, '0', STR_PAD_LEFT),
                    'rw' => str_pad((string) fake()->numberBetween(1, 8), 3, '0', STR_PAD_LEFT),
                    'kelurahan' => fake()->randomElement(['Palangka', 'Langkai', 'Menteng', 'Bukit Tunggal', 'Panarung']),
                    'kecamatan' => fake()->randomElement(['Jekan Raya', 'Pahandut', 'Sabangau', 'Bukit Batu']),
                    'kode_pos' => fake()->randomElement(['73111', '73112', '73113', '73114', '73115']),
                ])
                ->create();

            FamilyMember::factory()
                ->forFamily($family)
                ->forCitizen($head)
                ->relation('head')
                ->state(['urutan' => 1])
                ->create();

            $spouse = Citizen::factory()
                ->forTenant($tenant)
                ->female()
                ->state([
                    'tanggal_lahir' => fake()->dateTimeBetween('-60 years', '-25 years')->format('Y-m-d'),
                    'status_perkawinan' => 'married',
                ])
                ->create();

            FamilyMember::factory()
                ->forFamily($family)
                ->forCitizen($spouse)
                ->relation('spouse')
                ->state(['urutan' => 2])
                ->create();

            $childCount = fake()->numberBetween(1, 3);

            foreach (range(1, $childCount) as $childNumber) {
                $child = Citizen::factory()
                    ->forTenant($tenant)
                    ->state([
                        'tanggal_lahir' => fake()->dateTimeBetween('-18 years', '-1 year')->format('Y-m-d'),
                        'status_perkawinan' => 'single',
                    ])
                    ->create();

                FamilyMember::factory()
                    ->forFamily($family)
                    ->forCitizen($child)
                    ->relation('child')
                    ->state(['urutan' => 2 + $childNumber])
                    ->create();
            }
        }

        $this->command?->info('Selesai: 20 KK demo beserta anggota keluarga berhasil dibuat.');
    }
}
