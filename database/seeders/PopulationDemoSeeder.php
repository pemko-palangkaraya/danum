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
    private const POSTAL_CODES = [
        'Pahandut|Langkai' => '73111',
        'Jekan Raya|Menteng' => '73111',
        'Jekan Raya|Bukit Tunggal' => '73112',
        'Jekan Raya|Palangka' => '73112',
        'Bukit Batu|Banturung' => '73224',
        'Bukit Batu|Sei Gohong' => '73225',
        'Rakumpit|Mungku Baru' => '73229',
        'Rakumpit|Panjehang' => '73228',
        'Rakumpit|Petuk Bukit' => '73227',
    ];

    public function run(): void
    {
        $tenant = Tenant::query()
            ->where('code', env('DANUM_DEMO_TENANT_CODE', 'DEMO001'))
            ->first();

        if ($tenant === null) {
            throw new \RuntimeException('Demo tenant tidak ditemukan. Jalankan DatabaseSeeder terlebih dahulu.');
        }

        $location = $this->tenantLocation($tenant);
        $this->command?->info(sprintf(
            'Membuat data kependudukan demo untuk tenant %s (%s, %s)...',
            $tenant->name,
            $location['village'],
            $location['district'],
        ));

        foreach (range(1, 200) as $familyNumber) {
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
                    'kelurahan' => $location['village'],
                    'kecamatan' => $location['district'],
                    'kabupaten_kota' => $tenant->city,
                    'provinsi' => $tenant->province,
                    'kode_pos' => $location['kode_pos'],
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
                    'tanggal_lahir' => fake()->dateTimeBetween('-90 years', '-25 years')->format('Y-m-d'),
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

        $this->command?->info('Selesai: 200 KK demo beserta anggota keluarga berhasil dibuat.');
    }

    private function tenantLocation(Tenant $tenant): array
    {
        $district = trim((string) $tenant->district);
        $village = trim((string) $tenant->village);

        if ($district === '' || $village === '') {
            throw new \RuntimeException('Tenant demo harus memiliki kecamatan dan kelurahan sebelum data kependudukan dibuat.');
        }

        return [
            'district' => $district,
            'village' => $village,
            'kode_pos' => self::POSTAL_CODES[$district . '|' . $village] ?? null,
        ];
    }
}
