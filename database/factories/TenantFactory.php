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

    /**
     * Kota Palangka Raya terdiri dari 5 kecamatan dan 30 kelurahan.
     * Data ini dipakai khusus untuk kebutuhan factory/test fixture.
     *
     * @var array<string, array<int, string>>
     */
    private const PALANGKA_RAYA_AREAS = [
        'Pahandut' => [
            'Pahandut', 'Panarung', 'Langkai', 'Tumbang Rungan', 'Tanjung Pinang', 'Pahandut Seberang',
        ],
        'Jekan Raya' => [
            'Menteng', 'Palangka', 'Bukit Tunggal', 'Petuk Katimpun',
        ],
        'Sabangau' => [
            'Kereng Bangkirai', 'Sabaru', 'Kalampangan', 'Kameloh Baru', 'Danau Tundai', 'Bereng Bengkel',
        ],
        'Bukit Batu' => [
            'Marang', 'Tumbang Tahai', 'Banturung', 'Tangkiling', 'Sei Gohong', 'Kanarakan', 'Habaring Hurung',
        ],
        'Rakumpit' => [
            'Petuk Bukit', 'Pager', 'Panjehang', 'Gaung Baru', 'Petuk Berunai', 'Mungku Baru', 'Bukit Sua',
        ],
    ];

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
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

    /**
     * Generate a realistic tenant anywhere under Pemerintah Kota Palangka Raya.
     */
    public function palangkaRaya(): static
    {
        return $this->state(function (): array {
            $district = fake()->randomElement(array_keys(self::PALANGKA_RAYA_AREAS));
            $village = fake()->randomElement(self::PALANGKA_RAYA_AREAS[$district]);

            return [
                'code' => fake()->unique()->regexify('PLK[0-9]{6}'),
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

    /**
     * Generate the Pemerintah Kota Palangka Raya tenant.
     */
    public function pemerintahKotaPalangkaRaya(): static
    {
        return $this->state([
            'code' => 'PEMKOT-PLK',
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

    /**
     * Generate a tenant for one of the five Palangka Raya kecamatan.
     */
    public function kecamatanPalangkaRaya(?string $district = null): static
    {
        return $this->state(function () use ($district): array {
            $district ??= fake()->randomElement(array_keys(self::PALANGKA_RAYA_AREAS));

            return [
                'code' => 'KEC-PLK-'.str()->upper(str()->slug($district)),
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

    /**
     * Generate a tenant for one of the 30 Palangka Raya kelurahan.
     */
    public function kelurahanPalangkaRaya(?string $district = null, ?string $village = null): static
    {
        return $this->state(function () use ($district, $village): array {
            $district ??= fake()->randomElement(array_keys(self::PALANGKA_RAYA_AREAS));
            $village ??= fake()->randomElement(self::PALANGKA_RAYA_AREAS[$district]);

            return [
                'code' => 'KEL-PLK-'.str()->upper(str()->slug($district)).'-'.str()->upper(str()->slug($village)),
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

    /**
     * Return all 30 Palangka Raya kelurahan as simple location fixtures.
     *
     * @return array<int, array<string, string>>
     */
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
        return $this->state(fn (): array => [
            'status' => TenantStatus::INACTIVE,
        ]);
    }

    private function categoryId(string $code): ?int
    {
        return TenantCategory::query()->where('code', $code)->value('id');
    }
}
