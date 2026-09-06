<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    private const PALANGKA_RAYA_AREAS = [
        'Pahandut' => ['Pahandut', 'Panarung', 'Langkai', 'Tumbang Rungan', 'Tanjung Pinang', 'Pahandut Seberang'],
        'Jekan Raya' => ['Menteng', 'Palangka', 'Bukit Tunggal', 'Petuk Katimpun'],
        'Sabangau' => ['Kereng Bangkirai', 'Sabaru', 'Kalampangan', 'Kameloh Baru', 'Danau Tundai', 'Bereng Bengkel'],
        'Bukit Batu' => ['Marang', 'Tumbang Tahai', 'Banturung', 'Tangkiling', 'Sei Gohong', 'Kanarakan', 'Habaring Hurung'],
        'Rakumpit' => ['Petuk Bukit', 'Pager', 'Panjehang', 'Gaung Baru', 'Petuk Berunai', 'Mungku Baru', 'Bukit Sua'],
    ];

    private const DISTRICT_CODES = [
        'Pahandut' => 'PHD',
        'Jekan Raya' => 'JKR',
        'Sabangau' => 'SBG',
        'Bukit Batu' => 'BKB',
        'Rakumpit' => 'RKP',
    ];

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('TNT[0-9]{3}'),
            'name' => fake()->company(),
            'tenant_category_id' => TenantCategory::query()->where('code', 'lainnya')->value('id'),
            'province' => fake()->state(),
            'city' => fake()->city(),
            'district' => fake()->city(),
            'village' => fake()->city(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'logo' => null,
            'head_name' => fake()->name(),
            'head_title' => 'Kepala Unit',
            'status' => TenantStatus::ACTIVE,
        ];
    }

    public function palangkaRaya(): static
    {
        return $this->state(function (): array {
            $district = fake()->randomElement(array_keys(self::PALANGKA_RAYA_AREAS));
            $village = fake()->randomElement(self::PALANGKA_RAYA_AREAS[$district]);

            return [
                'code' => $this->villageCode($district, $village),
                'name' => "Kelurahan {$village}",
                'tenant_category_id' => $this->categoryId('kelurahan'),
                'province' => 'Kalimantan Tengah',
                'city' => 'Palangka Raya',
                'district' => $district,
                'village' => $village,
                'address' => "Kelurahan {$village}, Kecamatan {$district}, Kota Palangka Raya",
                'head_name' => fake()->name(),
                'head_title' => 'Lurah',
            ];
        });
    }

    public function pemerintahKotaPalangkaRaya(): static
    {
        return $this->state([
            'code' => 'PLK',
            'name' => 'Pemerintah Kota Palangka Raya',
            'tenant_category_id' => $this->categoryId('pemerintah-kota'),
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Pahandut',
            'village' => 'Langkai',
            'head_name' => 'Wali Kota Palangka Raya',
            'head_title' => 'Wali Kota',
        ]);
    }

    public function kecamatanPalangkaRaya(?string $district = null): static
    {
        return $this->state(function () use ($district): array {
            $district ??= fake()->randomElement(array_keys(self::PALANGKA_RAYA_AREAS));

            return [
                'code' => self::DISTRICT_CODES[$district] ?? 'K'.fake()->numerify('##'),
                'name' => "Kecamatan {$district}",
                'tenant_category_id' => $this->categoryId('kecamatan'),
                'province' => 'Kalimantan Tengah',
                'city' => 'Palangka Raya',
                'district' => $district,
                'village' => self::PALANGKA_RAYA_AREAS[$district][0],
                'head_name' => fake()->name(),
                'head_title' => 'Camat',
            ];
        });
    }

    public function kelurahanPalangkaRaya(?string $district = null, ?string $village = null): static
    {
        return $this->state(function () use ($district, $village): array {
            $district ??= fake()->randomElement(array_keys(self::PALANGKA_RAYA_AREAS));
            $village ??= fake()->randomElement(self::PALANGKA_RAYA_AREAS[$district]);

            return [
                'code' => $this->villageCode($district, $village),
                'name' => "Kelurahan {$village}",
                'tenant_category_id' => $this->categoryId('kelurahan'),
                'province' => 'Kalimantan Tengah',
                'city' => 'Palangka Raya',
                'district' => $district,
                'village' => $village,
                'address' => "Kelurahan {$village}, Kecamatan {$district}, Kota Palangka Raya",
                'head_name' => fake()->name(),
                'head_title' => 'Lurah',
            ];
        });
    }

    public static function palangkaRayaKelurahanData(): array
    {
        $rows = [];
        foreach (self::PALANGKA_RAYA_AREAS as $district => $villages) {
            foreach ($villages as $village) {
                $rows[] = [
                    'name' => "Kelurahan {$village}",
                    'province' => 'Kalimantan Tengah',
                    'city' => 'Palangka Raya',
                    'district' => $district,
                    'village' => $village,
                ];
            }
        }
        return $rows;
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => TenantStatus::INACTIVE]);
    }

    private function categoryId(string $code): ?int
    {
        return TenantCategory::query()->where('code', $code)->value('id');
    }

    private function villageCode(string $district, string $village): string
    {
        $index = array_search($village, self::PALANGKA_RAYA_AREAS[$district], true);
        $sequence = str_pad((string) (($index === false ? 0 : $index) + 1), 2, '0', STR_PAD_LEFT);
        return (self::DISTRICT_CODES[$district] ?? 'KEL').'-'.$sequence;
    }
}
