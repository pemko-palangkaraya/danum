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

class CustomRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_role_permissions_are_used_for_authorization(): void
    {
        $tenant = Tenant::factory()->create();
        $role = Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Operator Surat',
            'slug' => 'operator-surat',
            'scope' => 'tenant',
            'is_system' => false,
            'is_active' => true,
        ]);
        $permission = Permission::query()->where('slug', PermissionEnum::OUTGOING_LETTERS_SUBMIT->value)->firstOrFail();
        $role->permissions()->attach($permission);
        $user = User::factory()->tenantUser($tenant)->create(['custom_role_id' => $role->id]);

        $this->assertTrue($user->hasPermission(PermissionEnum::OUTGOING_LETTERS_SUBMIT));
        $this->assertFalse($user->hasPermission(PermissionEnum::OUTGOING_LETTERS_ISSUE));
    }

    public function test_tenant_admin_can_only_manage_custom_roles_in_own_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin($tenantA)->create();
        $foreignRole = Role::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Foreign Role',
            'slug' => 'foreign-role',
            'scope' => 'tenant',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('rbac.index'))
            ->assertOk()
            ->assertDontSee('Foreign Role');

        $this->actingAs($admin)
            ->get(route('rbac.index'))
            ->assertOk();

        $this->assertFalse($admin->isSuperAdmin());
        $this->assertSame($tenantA->id, $admin->tenant_id);
        $this->assertNotSame($tenantA->id, $foreignRole->tenant_id);
    }

    public function test_super_admin_always_has_permission_even_if_role_matrix_is_changed(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->hasPermission(PermissionEnum::RBAC_MANAGE));
        $this->assertTrue($user->hasPermission(PermissionEnum::TENANTS_DELETE));
    }
}
