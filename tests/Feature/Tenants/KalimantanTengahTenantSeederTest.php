<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KalimantanTengahTenantSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_palangka_raya_seeder_builds_explicit_city_district_village_hierarchy(): void
    {
        $districts = [
            ['code' => '62.71.01', 'name' => 'Pahandut'],
            ['code' => '62.71.02', 'name' => 'Jekan Raya'],
            ['code' => '62.71.03', 'name' => 'Bukit Batu'],
            ['code' => '62.71.04', 'name' => 'Rakumpit'],
            ['code' => '62.71.05', 'name' => 'Sabangau'],
        ];

        $villageResponses = [];
        foreach ($districts as $district) {
            $villageResponses[$district['code']] = collect(range(1, 6))
                ->map(fn (int $number): array => [
                    'code' => $district['code'].'.1'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                    'name' => $district['name'].' Village '.$number,
                ])
                ->all();
        }

        Http::fake([
            '*/regencies/62.json' => Http::response([
                'data' => [
                    ['code' => '62.71', 'name' => 'Palangka Raya'],
                ],
            ]),
            '*/districts/62.71.json' => Http::response(['data' => $districts]),
            '*/villages/*' => function ($request) use ($villageResponses) {
                $code = basename(parse_url($request->url(), PHP_URL_PATH));
                return Http::response(['data' => $villageResponses[$code] ?? []]);
            },
        ]);

        $this->seed(\Database\Seeders\KalimantanTengahTenantSeeder::class);

        $city = Tenant::query()
            ->where('code', 'wilayah-62-71')
            ->firstOrFail();

        $districtTenants = Tenant::query()
            ->where('parent_tenant_id', $city->id)
            ->whereHas('category', fn ($query) => $query->where('code', 'kecamatan'))
            ->get();

        $villageTenants = Tenant::query()
            ->whereHas('category', fn ($query) => $query->where('code', 'kelurahan'))
            ->get();

        $this->assertCount(1, Tenant::query()->where('code', 'wilayah-62-71')->get());
        $this->assertCount(5, $districtTenants);
        $this->assertCount(30, $villageTenants);
        $this->assertSame(5, $districtTenants->pluck('parent_tenant_id')->unique()->count());
        $this->assertSame(5, $villageTenants->pluck('parent_tenant_id')->unique()->count());

        foreach ($districtTenants as $district) {
            $this->assertSame($city->id, $district->parent_tenant_id);
            $this->assertSame(6, $district->children()->count());
        }
    }
}
