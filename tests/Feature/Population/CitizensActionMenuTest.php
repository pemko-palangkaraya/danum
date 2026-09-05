<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Livewire\Population\Citizens;
use App\Models\Citizen;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CitizensActionMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_actions_are_rendered_as_three_dot_menu(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        Citizen::factory()->forTenant($tenant)->create();

        Livewire::actingAs($user)
            ->test(Citizens::class)
            ->assertSee('Citizen actions')
            ->assertSee('Detail')
            ->assertSee('Edit');
    }

    public function test_citizen_action_menu_hides_edit_without_population_manage_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        Citizen::factory()->forTenant($tenant)->create();

        Livewire::actingAs($user)
            ->test(Citizens::class)
            ->assertSee('Citizen actions')
            ->assertSee('Detail')
            ->assertDontSee('Edit');
    }
}
