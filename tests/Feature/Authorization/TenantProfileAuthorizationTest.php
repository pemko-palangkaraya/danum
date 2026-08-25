<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProfileAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_view_own_organization_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();

        $this->assertTrue($user->can('viewProfile', $tenant));
    }

    public function test_tenant_user_can_view_own_organization_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $this->assertTrue($user->can('viewProfile', $tenant));
    }

    public function test_tenant_user_cannot_view_another_organizations_profile(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($ownTenant)->create();

        $this->assertFalse($user->can('viewProfile', $otherTenant));
    }

    public function test_tenant_admin_cannot_update_organization_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();

        $this->assertFalse($user->can('updateProfile', $tenant));
    }

    public function test_super_admin_can_update_organization_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('updateProfile', $tenant));
    }

    public function test_super_admin_can_view_organization_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('view', $tenant));
    }
}
