<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_has_full_user_management_access(): void
    {
        $tenant = Tenant::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $target = User::factory()->tenantUser($tenant)->create();

        $this->assertTrue($superAdmin->can('viewAny', User::class));
        $this->assertTrue($superAdmin->can('view', $target));
        $this->assertTrue($superAdmin->can('create', User::class));
        $this->assertTrue($superAdmin->can('update', $target));
        $this->assertTrue($superAdmin->can('delete', $target));
    }

    public function test_tenant_admin_can_manage_users_in_own_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin($tenant)->create();
        $target = User::factory()->tenantUser($tenant)->create();

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('view', $target));
        $this->assertTrue($admin->can('create', User::class));
        $this->assertTrue($admin->can('update', $target));
    }

    public function test_tenant_admin_cannot_manage_users_from_another_tenant(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin($ownTenant)->create();
        $target = User::factory()->tenantUser($otherTenant)->create();

        $this->assertFalse($admin->can('view', $target));
        $this->assertFalse($admin->can('update', $target));
    }

    public function test_tenant_admin_cannot_update_another_tenant_admin(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin($tenant)->create();
        $otherAdmin = User::factory()->tenantAdmin($tenant)->create();

        $this->assertFalse($admin->can('update', $otherAdmin));
    }

    public function test_tenant_user_cannot_manage_users(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $target = User::factory()->tenantUser($tenant)->create();

        $this->assertFalse($user->can('viewAny', User::class));
        $this->assertFalse($user->can('view', $target));
        $this->assertFalse($user->can('create', User::class));
        $this->assertFalse($user->can('update', $target));
        $this->assertFalse($user->can('delete', $target));
    }
}
