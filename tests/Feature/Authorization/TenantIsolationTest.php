<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_access_users_in_own_tenant_only(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin($ownTenant)->create();
        $ownUser = User::factory()->tenantUser($ownTenant)->create();
        $otherUser = User::factory()->tenantUser($otherTenant)->create();

        $this->assertTrue($admin->can('view', $ownUser));
        $this->assertTrue($admin->can('update', $ownUser));
        $this->assertFalse($admin->can('view', $otherUser));
        $this->assertFalse($admin->can('update', $otherUser));
    }

    public function test_tenant_user_cannot_cross_tenant_or_manage_users(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($ownTenant)->create();
        $otherUser = User::factory()->tenantUser($otherTenant)->create();

        $this->assertFalse($user->can('viewAny', User::class));
        $this->assertFalse($user->can('view', $otherUser));
        $this->assertFalse($user->can('update', $otherUser));
    }

    public function test_super_admin_can_cross_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $userA = User::factory()->tenantUser($tenantA)->create();
        $userB = User::factory()->tenantUser($tenantB)->create();

        $this->assertTrue($superAdmin->can('view', $userA));
        $this->assertTrue($superAdmin->can('view', $userB));
        $this->assertTrue($superAdmin->can('update', $userA));
        $this->assertTrue($superAdmin->can('update', $userB));
    }

    public function test_tenant_admin_cannot_update_target_by_changing_tenant_context(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin($ownTenant)->create();
        $target = User::factory()->tenantUser($otherTenant)->create();

        $this->assertFalse($admin->can('update', $target));
        $this->assertSame($otherTenant->id, $target->fresh()->tenant_id);
    }

    public function test_user_without_tenant_is_not_treated_as_member_of_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin($tenant)->create();
        $orphan = User::factory()->create([
            'role' => UserRole::TENANT_USER,
            'tenant_id' => null,
        ]);

        $this->assertFalse($admin->can('view', $orphan));
        $this->assertFalse($admin->can('update', $orphan));
    }
}
