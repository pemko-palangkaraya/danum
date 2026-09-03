<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopulationPermissionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_requires_population_manage_permission_not_a_specific_role(): void
    {
        $tenant = Tenant::factory()->create();
        $permission = Permission::query()->where('slug', PermissionEnum::POPULATION_MANAGE->value)->firstOrFail();
        $role = Role::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Population Operator',
            'slug' => 'population-operator',
            'scope' => 'tenant',
            'is_system' => false,
            'is_active' => true,
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'custom_role_id' => $role->id,
        ]);

        $this->assertTrue($user->hasPermission(PermissionEnum::POPULATION_MANAGE));

        $this->actingAs($user)
            ->get(route('population.citizens.import'))
            ->assertOk();
    }

    public function test_import_is_forbidden_without_population_manage_permission(): void
    {
        $user = User::factory()->tenantUser(Tenant::factory()->create())->create();

        $this->assertFalse($user->hasPermission(PermissionEnum::POPULATION_MANAGE));

        $this->actingAs($user)
            ->get(route('population.citizens.import'))
            ->assertForbidden();
    }
}
