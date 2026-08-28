<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacUiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

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

        $this->actingAs($user)
            ->get(route('rbac.index'))
            ->assertOk()
            ->assertSee('Role &amp; Access Control', false)
            ->assertSee('Tenant Admin')
            ->assertDontSee('Super Admin')
            ->assertDontSee('Tenant User');
    }

    public function test_tenant_user_cannot_access_rbac_ui(): void
    {
        $user = User::factory()->tenantUser(Tenant::factory()->create())->create();

        $this->actingAs($user)
            ->get(route('rbac.index'))
            ->assertForbidden();
    }
}
