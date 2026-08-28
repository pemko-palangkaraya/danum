<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permission_matrix_is_applied(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $tenantAdmin = User::factory()->tenantAdmin(Tenant::factory()->create())->create();
        $tenantUser = User::factory()->tenantUser(Tenant::factory()->create())->create();

        $this->assertTrue($superAdmin->hasPermission(Permission::AUDIT_LOGS_VIEW));
        $this->assertTrue($superAdmin->hasPermission(Permission::TENANTS_CREATE));

        $this->assertTrue($tenantAdmin->hasPermission(Permission::TENANT_USERS_VIEW));
        $this->assertTrue($tenantAdmin->hasPermission(Permission::POSITIONS_MANAGE));
        $this->assertFalse($tenantAdmin->hasPermission(Permission::TENANT_PROFILE_UPDATE));
        $this->assertFalse($tenantAdmin->hasPermission(Permission::AUDIT_LOGS_VIEW));

        $this->assertFalse($tenantUser->hasPermission(Permission::TENANT_USERS_VIEW));
        $this->assertTrue($tenantUser->hasPermission(Permission::OUTGOING_LETTERS_CREATE));
        $this->assertFalse($tenantUser->hasPermission(Permission::TENANTS_CREATE));
    }

    public function test_inactive_user_has_no_permissions(): void
    {
        $user = User::factory()->inactive()->create();

        $this->assertFalse($user->hasPermission(Permission::DASHBOARD_VIEW));
        $this->assertFalse($user->hasPermission(Permission::OUTGOING_LETTERS_VIEW));
    }

    public function test_permission_middleware_denies_a_role_without_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->tenantUser($tenant)->create();

        $this->actingAs($tenantUser)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_permission_middleware_allows_a_role_with_permission(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('audit-logs.index'))
            ->assertOk();
    }
}
