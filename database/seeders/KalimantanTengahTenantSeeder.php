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
    private const API = 'https://wilayah.id/api';

    public function run(): void
    {
        $categories = $this->categories();

        // Seeder ini khusus untuk wilayah yang berada di bawah
        // Pemerintah Kota Palangka Raya: 1 kota, 5 kecamatan,
        // dan seluruh kelurahan di dalamnya.
        $regency = collect($this->get('/regencies/'.self::PROVINCE_CODE.'.json'))
            ->firstWhere('code', self::PALANGKA_RAYA_CODE);

        if (!is_array($regency)) {
            throw new RuntimeException('Kota Palangka Raya tidak ditemukan pada data wilayah.');
        }

        $regencyCode = (string) $regency['code'];
        $regencyName = (string) $regency['name'];

        $this->seedTenant(
            $this->tenantCode($regencyCode),
            "Pemerintah {$regencyName}",
            $categories['pemerintah-kota'],
            $regencyName,
            'Pusat Pemerintahan',
            'Pusat Pemerintahan',
            "Wali Kota {$regencyName}",
            'Wali Kota',
        );

        $counts = [
            'city' => 1,
            'districts' => 0,
            'villages' => 0,
        ];

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

                // Palangka Raya berada pada wilayah perkotaan, sehingga
                // tenant tingkat desa yang tidak relevan tidak ikut dibuat.
                $suffix = substr(strrchr($villageCode, '.'), 1);
                if (!str_starts_with($suffix, '1')) {
                    continue;
                }

                $this->seedTenant(
                    $this->tenantCode($villageCode),
                    "Kelurahan {$villageName}",
                    $categories['kelurahan'],
                    $regencyName,
                    $districtName,
                    $villageName,
                    "Lurah {$villageName}",
                    'Lurah',
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
