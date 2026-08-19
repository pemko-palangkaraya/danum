<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_tenant_index(): void
    {
        $response = $this->getJson('/api/tenants');

        $response->assertUnauthorized();
    }

    public function test_tenant_user_cannot_access_tenant_index(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/api/tenants');

        $response->assertForbidden();
    }

    public function test_super_admin_can_access_tenant_index(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        Tenant::factory()->count(2)->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/api/tenants');

        $response->assertOk();
    }

    public function test_tenant_user_can_view_own_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->getJson("/api/tenants/{$tenant->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $tenant->id);
    }

    public function test_tenant_user_cannot_view_other_tenant(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($ownTenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->getJson("/api/tenants/{$otherTenant->id}");

        $response->assertForbidden();
    }

    public function test_tenant_user_cannot_create_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/tenants', [
                'code' => 'AUTH001',
                'name' => 'Unauthorized Tenant',
            ]);

        $response->assertForbidden();
    }

    public function test_tenant_user_cannot_update_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->putJson("/api/tenants/{$tenant->id}", [
                'name' => 'Unauthorized Update',
            ]);

        $response->assertForbidden();
    }

    public function test_tenant_user_cannot_delete_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->deleteJson("/api/tenants/{$tenant->id}");

        $response->assertForbidden();
    }

    public function test_tenant_user_cannot_restore_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $tenant->delete();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson("/api/tenants/{$tenant->id}/restore");

        $response->assertForbidden();
    }
}
