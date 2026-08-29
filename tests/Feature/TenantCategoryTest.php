<?php

declare(strict_types=1);

namespace TestsFeature;

use AppEnumsTenantStatus;
use AppModelsTenant;
use AppModelsTenantCategory;
use AppModelsUser;
use IlluminateFoundationTestingRefreshDatabase;
use TestsTestCase;

class TenantCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_contains_all_default_tenant_categories_in_order(): void
    {
        $this->assertSame(
            [
                'Sekretariat Daerah',
                'Sekretariat DPRD',
                'Inspektorat',
                'Dinas',
                'Badan',
                'Satuan Polisi Pamong Praja',
                'Kecamatan',
                'Kelurahan',
                'UPTD',
                'Unit Pelaksana Teknis',
                'Rumah Sakit Daerah',
                'Puskesmas',
                'Badan Layanan Umum Daerah (BLUD)',
                'Lainnya',
            ],
            TenantCategory::query()->orderBy('sort_order')->pluck('name')->all(),
        );
    }

    public function test_tenant_can_be_created_with_a_category(): void
    {
        $category = TenantCategory::query()->where('code', 'kelurahan')->firstOrFail();

        $tenant = Tenant::factory()->create([
            'tenant_category_id' => $category->id,
            'name' => 'Kelurahan Contoh',
        ]);

        $this->assertSame($category->id, $tenant->fresh()->category->id);
        $this->assertSame('Kelurahan', $tenant->fresh()->category->name);
    }

    public function test_tenant_service_defaults_missing_category_to_lainnya(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);

        $tenant = app(AppServicesTenantService::class)->create([
            'code' => 'CAT001',
            'name' => 'Tenant Tanpa Kategori',
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Demo',
            'village' => 'Demo',
            'status' => TenantStatus::ACTIVE,
        ]);

        $this->assertSame(
            'lainnya',
            $tenant->fresh()->category->code,
        );
    }
}
