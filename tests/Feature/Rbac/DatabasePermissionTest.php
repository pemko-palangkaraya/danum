<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabasePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_roles_and_permissions_are_seeded(): void
    {
        $this->assertDatabaseHas('roles', ['slug' => UserRole::SUPER_ADMIN->value, 'scope' => 'global']);
        $this->assertDatabaseHas('roles', ['slug' => UserRole::TENANT_ADMIN->value, 'scope' => 'tenant']);
        $this->assertDatabaseHas('roles', ['slug' => UserRole::TENANT_USER->value, 'scope' => 'tenant']);
        $this->assertDatabaseHas('permissions', ['slug' => PermissionEnum::RBAC_MANAGE->value, 'scope' => 'global']);
    }

    public function test_default_tenant_role_permission_matrix_is_seeded(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantAdmin = User::factory()->tenantAdmin($tenant)->create();
        $tenantUser = User::factory()->tenantUser($tenant)->create();

        $this->assertTrue($tenantAdmin->hasAllPermissions([
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::TENANT_USERS_VIEW,
            PermissionEnum::POSITIONS_MANAGE,
            PermissionEnum::OUTGOING_LETTERS_VALIDATE,
            PermissionEnum::OUTGOING_LETTERS_ISSUE,
            PermissionEnum::POPULATION_VIEW,
            PermissionEnum::POPULATION_MANAGE,
        ]));
        $this->assertFalse($tenantAdmin->hasPermission(PermissionEnum::TENANT_PROFILE_UPDATE));
        $this->assertFalse($tenantAdmin->hasPermission(PermissionEnum::AUDIT_LOGS_VIEW));
        $this->assertFalse($tenantAdmin->hasPermission(PermissionEnum::RBAC_MANAGE));

        $this->assertTrue($tenantUser->hasAllPermissions([
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::TENANT_PROFILE_VIEW,
            PermissionEnum::POSITIONS_VIEW,
            PermissionEnum::LETTER_TYPES_VIEW,
            PermissionEnum::OUTGOING_LETTERS_VIEW,
            PermissionEnum::OUTGOING_LETTERS_CREATE,
            PermissionEnum::OUTGOING_LETTERS_UPDATE,
            PermissionEnum::OUTGOING_LETTERS_DELETE,
            PermissionEnum::OUTGOING_LETTERS_SUBMIT,
            PermissionEnum::POPULATION_VIEW,
        ]));
        $this->assertFalse($tenantUser->hasPermission(PermissionEnum::TENANT_USERS_VIEW));
        $this->assertFalse($tenantUser->hasPermission(PermissionEnum::POPULATION_MANAGE));
        $this->assertFalse($tenantUser->hasPermission(PermissionEnum::OUTGOING_LETTERS_VALIDATE));
        $this->assertFalse($tenantUser->hasPermission(PermissionEnum::TENANTS_CREATE));
    }

    public function test_legacy_tenant_admin_without_custom_role_id_has_no_database_permissions(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'platform_role' => null,
            'custom_role_id' => null,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertFalse($user->hasPermission(PermissionEnum::POPULATION_MANAGE));
    }

    public function test_permission_is_resolved_from_role_permission_pivot(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();

        $this->assertTrue($user->hasPermission(PermissionEnum::POSITIONS_MANAGE));

        $role = Role::query()->where('slug', UserRole::TENANT_ADMIN->value)->firstOrFail();
        $permission = Permission::query()->where('slug', PermissionEnum::POSITIONS_MANAGE->value)->firstOrFail();
        $role->permissions()->detach($permission->id);

        $this->assertFalse($user->fresh()->hasPermission(PermissionEnum::POSITIONS_MANAGE));
    }

    public function test_rbac_manage_is_only_granted_to_super_admin_by_default(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $tenantAdmin = User::factory()->tenantAdmin(Tenant::factory()->create())->create();
        $tenantUser = User::factory()->tenantUser(Tenant::factory()->create())->create();

        $this->assertTrue($superAdmin->hasPermission(PermissionEnum::RBAC_MANAGE));
        $this->assertFalse($tenantAdmin->hasPermission(PermissionEnum::RBAC_MANAGE));
        $this->assertFalse($tenantUser->hasPermission(PermissionEnum::RBAC_MANAGE));
    }

    public function test_inactive_user_has_no_database_permissions(): void
    {
        $user = User::factory()->superAdmin()->inactive()->create();

        $this->assertFalse($user->hasPermission(PermissionEnum::RBAC_MANAGE));
    }
}
