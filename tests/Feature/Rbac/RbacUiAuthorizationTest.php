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

class RbacUiAuthorizationTest extends TestCase
{
    public function test_super_admin_can_access_rbac_ui(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('rbac.index'))
            ->assertOk()
            ->assertSee('Role &amp; Access Control', false)
            ->assertSee('Super Admin')
            ->assertSee('Tenant Admin')
            ->assertSee('Tenant User');
    }

    public function test_tenant_admin_can_access_rbac_ui_but_only_sees_tenant_admin_role(): void
    {
        $user = User::factory()->tenantAdmin(Tenant::factory()->create())->create();

        $response = $this->actingAs($user)->get(route('rbac.index'));

        $response
            ->assertOk()
            ->assertSee('Role &amp; Access Control', false)
            ->assertSee('<h2 class="text-sm font-semibold text-slate-900">Tenant Admin</h2>', false)
            ->assertDontSee('<h2 class="text-sm font-semibold text-slate-900">Super Admin</h2>', false)
            ->assertDontSee('<h2 class="text-sm font-semibold text-slate-900">Tenant User</h2>', false);
    }

    public function test_tenant_user_cannot_access_rbac_ui(): void
    {
        $user = User::factory()->tenantUser(Tenant::factory()->create())->create();

        $this->actingAs($user)
            ->get(route('rbac.index'))
            ->assertForbidden();
    }
    public function test_tenant_admin_sidebar_uses_tenant_routes_after_system_role_permissions_change(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        $role = Role::query()->where('slug', UserRole::TENANT_ADMIN->value)->firstOrFail();
        $positionsView = Permission::query()->where('slug', PermissionEnum::POSITIONS_VIEW->value)->firstOrFail();

        $role->permissions()->detach($positionsView);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('positions.admin.index'), false)
            ->assertDontSee('href="' . route('positions.admin.index') . '"', false);

        $this->actingAs($user)
            ->get(route('positions.index'))
            ->assertForbidden();

        $role->permissions()->attach($positionsView);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="' . route('positions.index') . '"', false)
            ->assertDontSee('href="' . route('positions.admin.index') . '"', false);

        $this->actingAs($user)
            ->get(route('positions.index'))
            ->assertOk();
    }

}
