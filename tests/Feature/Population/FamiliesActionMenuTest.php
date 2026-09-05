<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Livewire\Population\Families;
use App\Models\Family;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FamiliesActionMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_actions_are_rendered_as_three_dot_menu(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        $family = Family::factory()->forTenant($tenant)->create();

        Livewire::actingAs($user)
            ->test(Families::class)
            ->assertSee('Family actions')
            ->assertSee('Detail')
            ->assertSee('Cetak KK')
            ->assertSee('Edit');

        $this->assertNotNull($family);
    }

    public function test_family_action_menu_hides_edit_without_population_manage_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        Family::factory()->forTenant($tenant)->create();

        Livewire::actingAs($user)
            ->test(Families::class)
            ->assertSee('Family actions')
            ->assertSee('Detail')
            ->assertSee('Cetak KK')
            ->assertDontSee('Edit');
    }
}
