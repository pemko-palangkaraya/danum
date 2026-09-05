<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use App\Livewire\Tenants\Edit;
use App\Models\Tenant;
use App\Models\TenantCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantHierarchyUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_loads_existing_parent_tenant_and_exposes_parent_options(): void
    {
        $cityCategory = TenantCategory::query()->firstOrCreate(
            ['code' => 'pemerintah-kota'],
            ['name' => 'Pemerintah Kota', 'is_active' => true, 'sort_order' => 0],
        );
        $districtCategory = TenantCategory::query()->firstOrCreate(
            ['code' => 'kecamatan'],
            ['name' => 'Kecamatan', 'is_active' => true, 'sort_order' => 0],
        );
        $city = Tenant::factory()->create([
            'tenant_category_id' => $cityCategory->id,
            'name' => 'Pemerintah Kota Palangka Raya',
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Pusat Pemerintahan',
            'village' => 'Pusat Pemerintahan',
        ]);
        $tenant = Tenant::factory()->create([
            'tenant_category_id' => $districtCategory->id,
            'parent_tenant_id' => $city->id,
            'name' => 'Kecamatan Jekan Raya',
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Jekan Raya',
            'village' => 'Pusat Pemerintahan',
        ]);
        $user = User::factory()->superAdmin()->create();

        $component = Livewire::actingAs($user)->test(Edit::class, ['tenant' => $tenant->id]);

        $component
            ->assertSet('parent_tenant_id', $city->id)
            ->assertSee('Parent Tenant')
            ->assertSee($city->name);
    }
}
