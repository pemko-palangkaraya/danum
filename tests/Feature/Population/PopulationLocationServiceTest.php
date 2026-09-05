<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantCategory;
use App\Services\PopulationLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopulationLocationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_options_only_return_active_registered_children(): void
    {
        $cityCategory = TenantCategory::create([
            'code' => 'pemerintah-kota',
            'name' => 'Pemerintah Kota',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $districtCategory = TenantCategory::create([
            'code' => 'kecamatan',
            'name' => 'Kecamatan',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $villageCategory = TenantCategory::create([
            'code' => 'kelurahan',
            'name' => 'Kelurahan',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $city = Tenant::create([
            'code' => 'city-rakumpit',
            'name' => 'Pemerintah Kota Palangka Raya',
            'tenant_category_id' => $cityCategory->id,
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'status' => TenantStatus::ACTIVE,
        ]);

        $rakumpit = Tenant::create([
            'code' => 'district-rakumpit',
            'name' => 'Kecamatan Rakumpit',
            'tenant_category_id' => $districtCategory->id,
            'parent_tenant_id' => $city->id,
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Rakumpit',
            'status' => TenantStatus::ACTIVE,
        ]);

        $mungkuBaru = Tenant::create([
            'code' => 'village-mungku-baru',
            'name' => 'Kelurahan Mungku Baru',
            'tenant_category_id' => $villageCategory->id,
            'parent_tenant_id' => $rakumpit->id,
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Rakumpit',
            'village' => 'Mungku Baru',
            'status' => TenantStatus::ACTIVE,
        ]);

        Tenant::create([
            'code' => 'village-bukit-sua',
            'name' => 'Kelurahan Bukit Sua',
            'tenant_category_id' => $villageCategory->id,
            'parent_tenant_id' => $rakumpit->id,
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Rakumpit',
            'village' => 'Bukit Sua',
            'status' => TenantStatus::ACTIVE,
        ]);

        Tenant::create([
            'code' => 'village-not-registered',
            'name' => 'Kelurahan Pager',
            'tenant_category_id' => $villageCategory->id,
            'parent_tenant_id' => null,
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Rakumpit',
            'village' => 'Pager',
            'status' => TenantStatus::ACTIVE,
        ]);

        Tenant::create([
            'code' => 'village-inactive',
            'name' => 'Kelurahan Petuk Barunai',
            'tenant_category_id' => $villageCategory->id,
            'parent_tenant_id' => $rakumpit->id,
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Rakumpit',
            'village' => 'Petuk Barunai',
            'status' => TenantStatus::INACTIVE,
        ]);

        $service = app(PopulationLocationService::class);

        $cityOptions = $service->optionsForTenant(
            $city->id,
            'Kalimantan Tengah',
            'Palangka Raya',
            'Rakumpit',
        );

        $this->assertSame(['Rakumpit'], $cityOptions['districts']->all());
        $this->assertSame(['Bukit Sua', 'Mungku Baru'], $cityOptions['villages']->all());
        $this->assertSame([
            'province' => true,
            'city' => true,
            'district' => false,
            'village' => false,
        ], $cityOptions['locks']);

        $districtOptions = $service->optionsForTenant(
            $rakumpit->id,
            'Kalimantan Tengah',
            'Palangka Raya',
            'Rakumpit',
        );

        $this->assertSame(['Rakumpit'], $districtOptions['districts']->all());
        $this->assertSame(['Bukit Sua', 'Mungku Baru'], $districtOptions['villages']->all());
        $this->assertSame([
            'province' => true,
            'city' => true,
            'district' => true,
            'village' => false,
        ], $districtOptions['locks']);
        $this->assertNotContains('Pager', $districtOptions['villages']);
        $this->assertNotContains('Petuk Barunai', $districtOptions['villages']);
        $this->assertSame('Mungku Baru', $mungkuBaru->village);

        $villageOptions = $service->optionsForTenant(
            $mungkuBaru->id,
            'Kalimantan Tengah',
            'Palangka Raya',
            'Rakumpit',
        );

        $this->assertSame(['Mungku Baru'], $villageOptions['villages']->all());
        $this->assertSame([
            'province' => true,
            'city' => true,
            'district' => true,
            'village' => true,
        ], $villageOptions['locks']);
        $this->assertSame([
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Rakumpit',
            'village' => 'Mungku Baru',
        ], $service->defaultsForTenant($mungkuBaru->id));
    }
}
