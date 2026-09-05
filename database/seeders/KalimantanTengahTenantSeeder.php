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
    private const PALANGKA_RAYA_CODE = '62.71';
    private const PALANGKA_RAYA_NAME = 'Palangka Raya';
    private const API = 'https://wilayah.id/api';

    public function run(): void
    {
        $categories = $this->categories();

        // Kalimantan Tengah seed ini adalah sumber master tenant wilayah:
        // Pemerintah Kota -> Kecamatan -> Kelurahan.
        $regency = collect($this->get('/regencies/'.self::PROVINCE_CODE.'.json'))
            ->firstWhere('code', self::PALANGKA_RAYA_CODE);

        if (!is_array($regency)) {
            throw new RuntimeException('Kota Palangka Raya tidak ditemukan pada data wilayah.');
        }

        $regencyCode = (string) $regency['code'];
        $cityName = self::PALANGKA_RAYA_NAME;

        $cityTenant = $this->seedTenant(
            $this->tenantCode($regencyCode),
            'Pemerintah Kota '.self::PALANGKA_RAYA_NAME,
            $categories['pemerintah-kota'],
            $cityName,
            'Pusat Pemerintahan',
            'Pusat Pemerintahan',
            'Wali Kota '.self::PALANGKA_RAYA_NAME,
            'Wali Kota',
            null,
        );

        $counts = [
            'city' => 1,
            'districts' => 0,
            'villages' => 0,
        ];

        foreach ($this->get('/districts/'.$regencyCode.'.json') as $district) {
            $districtCode = (string) $district['code'];
            $districtName = (string) $district['name'];

            $districtTenant = $this->seedTenant(
                $this->tenantCode($districtCode),
                "Kecamatan {$districtName}",
                $categories['kecamatan'],
                $cityName,
                $districtName,
                'Pusat Pemerintahan',
                "Camat {$districtName}",
                'Camat',
                $cityTenant->id,
            );
            $counts['districts']++;

            foreach ($this->get('/villages/'.$districtCode.'.json') as $village) {
                $villageCode = (string) $village['code'];
                $villageName = (string) $village['name'];

                // Palangka Raya adalah wilayah perkotaan, sehingga tenant
                // tingkat wilayah di bawah kecamatan menggunakan kategori kelurahan.
                $suffix = substr(strrchr($villageCode, '.'), 1);
                if (!str_starts_with($suffix, '1')) {
                    continue;
                }

                $this->seedTenant(
                    $this->tenantCode($villageCode),
                    "Kelurahan {$villageName}",
                    $categories['kelurahan'],
                    $cityName,
                    $districtName,
                    $villageName,
                    "Lurah {$villageName}",
                    'Lurah',
                    $districtTenant->id,
                );
                $counts['villages']++;
            }
        }

        $this->command?->info(sprintf(
            'Palangka Raya seeded: %d kota, %d kecamatan, %d kelurahan.',
            $counts['city'],
            $counts['districts'],
            $counts['villages'],
        ));
    }

    private function categories(): array
    {
        $names = [
            'pemerintah-kota' => 'Pemerintah Kota',
            'kecamatan' => 'Kecamatan',
            'kelurahan' => 'Kelurahan',
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
        ?string $parentTenantId,
    ): Tenant {
        return Tenant::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'tenant_category_id' => $categoryId,
                'parent_tenant_id' => $parentTenantId,
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
