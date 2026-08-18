<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_any_tenants(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $this->assertTrue(
            $user->can('viewAny', Tenant::class),
        );
    }

    public function test_super_admin_can_view_any_tenant(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $tenant = Tenant::factory()->create();

        $this->assertTrue(
            $user->can('view', $tenant),
        );
    }

    public function test_super_admin_can_create_tenant(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $this->assertTrue(
            $user->can('create', Tenant::class),
        );
    }

    public function test_super_admin_can_update_tenant(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $tenant = Tenant::factory()->create();

        $this->assertTrue(
            $user->can('update', $tenant),
        );
    }

    public function test_super_admin_can_delete_tenant(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $tenant = Tenant::factory()->create();

        $this->assertTrue(
            $user->can('delete', $tenant),
        );
    }

    public function test_super_admin_can_restore_tenant(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $tenant = Tenant::factory()->create();

        $this->assertTrue(
            $user->can('restore', $tenant),
        );
    }

    public function test_tenant_user_can_view_own_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertTrue(
            $user->can('view', $tenant),
        );
    }

    public function test_tenant_user_cannot_view_other_tenant(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($ownTenant)
            ->create();

        $this->assertFalse(
            $user->can('view', $otherTenant),
        );
    }

    public function test_tenant_user_cannot_view_any_tenants(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertFalse(
            $user->can('viewAny', Tenant::class),
        );
    }

    public function test_tenant_user_cannot_create_tenant(): void
    {
        $user = User::factory()
            ->tenantUser()
            ->create();

        $this->assertFalse(
            $user->can('create', Tenant::class),
        );
    }

    public function test_tenant_user_cannot_update_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertFalse(
            $user->can('update', $tenant),
        );
    }

    public function test_tenant_user_cannot_delete_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertFalse(
            $user->can('delete', $tenant),
        );
    }

    public function test_tenant_user_cannot_restore_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertFalse(
            $user->can('restore', $tenant),
        );
    }
}