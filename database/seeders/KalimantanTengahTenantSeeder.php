<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KalimantanTengahTenantSeeder extends Seeder
{
    private const PROVINCE_CODE = '62';
    private const API = 'https://wilayah.id/api';

    public function run(): void
    {
        $categories = $this->categories();

        // Tenant tingkat provinsi tetap harus memenuhi kolom wilayah wajib
        // pada tabel tenants, sehingga menggunakan lokasi pusat pemerintahan.
        $this->seedTenant(
            'wilayah-62',
            'Pemerintah Provinsi Kalimantan Tengah',
            $categories['pemerintah-provinsi'],
            'Palangka Raya',
            'Jekan Raya',
            'Menteng',
            'Gubernur Kalimantan Tengah',
            'Gubernur',
        );

        $regencies = $this->get('/regencies/'.self::PROVINCE_CODE.'.json');
        $counts = ['regencies' => 0, 'districts' => 0, 'villages' => 0];

        foreach ($regencies as $regency) {
            $regencyCode = (string) $regency['code'];
            $regencyName = (string) $regency['name'];
            $isCity = str_ends_with($regencyCode, '.71');
            $regencyCategory = $isCity ? $categories['pemerintah-kota'] : $categories['pemerintah-kabupaten'];

            $this->seedTenant(
                $this->tenantCode($regencyCode),
                $isCity ? "Pemerintah {$regencyName}" : "Pemerintah Kabupaten {$regencyName}",
                $regencyCategory,
                $regencyName,
                'Pusat Pemerintahan',
                'Pusat Pemerintahan',
                $isCity ? "Wali Kota {$regencyName}" : "Bupati {$regencyName}",
                $isCity ? 'Wali Kota' : 'Bupati',
            );
            $counts['regencies']++;

            foreach ($this->get('/districts/'.$regencyCode.'.json') as $district) {
                $districtCode = (string) $district['code'];
                $districtName = (string) $district['name'];

                $this->seedTenant(
                    $this->tenantCode($districtCode),
                    "Kecamatan {$districtName}",
                    $categories['kecamatan'],
                    $regencyName,
                    $districtName,
                    'Pusat Pemerintahan',
                    "Camat {$districtName}",
                    'Camat',
                );
                $counts['districts']++;

                foreach ($this->get('/villages/'.$districtCode.'.json') as $village) {
                    $villageCode = (string) $village['code'];
                    $villageName = (string) $village['name'];
                    $suffix = substr(strrchr($villageCode, '.'), 1);
                    $isKelurahan = str_starts_with($suffix, '1');
                    $category = $isKelurahan ? $categories['kelurahan'] : $categories['desa'];

                    $this->seedTenant(
                        $this->tenantCode($villageCode),
                        ($isKelurahan ? 'Kelurahan ' : 'Desa ').$villageName,
                        $category,
                        $regencyName,
                        $districtName,
                        $villageName,
                        ($isKelurahan ? 'Lurah ' : 'Kepala Desa ').$villageName,
                        $isKelurahan ? 'Lurah' : 'Kepala Desa',
                    );
                    $counts['villages']++;
                }
            }
        }

        $this->command?->info(sprintf(
            'Kalimantan Tengah seeded: %d kabupaten/kota, %d kecamatan, %d desa/kelurahan.',
            $counts['regencies'],
            $counts['districts'],
            $counts['villages'],
        ));
    }

    private function categories(): array
    {
        $names = [
            'pemerintah-provinsi' => 'Pemerintah Provinsi',
            'pemerintah-kabupaten' => 'Pemerintah Kabupaten',
            'pemerintah-kota' => 'Pemerintah Kota',
            'kecamatan' => 'Kecamatan',
            'kelurahan' => 'Kelurahan',
            'desa' => 'Pemerintahan Desa',
        ];

        $result = [];
        foreach ($names as $code => $name) {
            $result[$code] = TenantCategory::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true],
            )->id;
        }

        return $result;
    }

    private function get(string $path): array
    {
        $response = Http::acceptJson()
            ->retry(3, 300)
            ->timeout(30)
            ->get(self::API.$path);

        if ($response->failed()) {
            throw new RuntimeException("Gagal mengambil data wilayah: {$response->status()} {$path}");
        }

        $data = $response->json('data');
        if (!is_array($data)) {
            throw new RuntimeException("Format response wilayah tidak valid: {$path}");
        }

        return $data;
    }

    private function seedTenant(
        string $code,
        string $name,
        int $categoryId,
        string $city,
        string $district,
        string $village,
        string $headName,
        string $headTitle,
    ): void {
        Tenant::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'tenant_category_id' => $categoryId,
                'province' => 'Kalimantan Tengah',
                'city' => $city,
                'district' => $district,
                'village' => $village,
                'address' => null,
                'phone' => null,
                'email' => null,
                'logo' => null,
                'letterhead_path' => null,
                'head_name' => $headName,
                'head_title' => $headTitle,
                'status' => TenantStatus::ACTIVE,
            ],
        );
    }

    private function tenantCode(string $regionCode): string
    {
        return 'wilayah-'.str_replace('.', '-', $regionCode);
    }
}
