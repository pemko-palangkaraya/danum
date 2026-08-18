<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_factory_can_create_active_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'code' => $tenant->code,
            'name' => $tenant->name,
            'status' => TenantStatus::ACTIVE->value,
        ]);
    }

    public function test_tenant_factory_can_create_inactive_tenant(): void
    {
        $tenant = Tenant::factory()->inactive()->create();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'code' => $tenant->code,
            'status' => TenantStatus::INACTIVE->value,
        ]);
    }
}