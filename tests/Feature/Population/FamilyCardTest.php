<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Models\Family;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_print_a_family_card_as_pdf(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $family = Family::factory()->forTenant($tenant)->create();

        $this->actingAs($user)
            ->get(route('population.families.pdf', ['id' => $family->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_tenant_user_cannot_print_another_tenants_family_card(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $family = Family::factory()->forTenant($otherTenant)->create();

        $this->actingAs($user)
            ->get(route('population.families.pdf', ['id' => $family->id]))
            ->assertNotFound();
    }

    public function test_user_without_population_view_cannot_print_a_family_card(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $family = Family::factory()->forTenant($tenant)->create();

        $role = $user->role;
        $this->assertNotNull($role);
        $role->permissions()->detach();

        $this->actingAs($user->fresh())
            ->get(route('population.families.pdf', ['id' => $family->id]))
            ->assertForbidden();
    }
}
