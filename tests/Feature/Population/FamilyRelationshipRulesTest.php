<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Livewire\Population\Families;
use App\Models\Citizen;
use App\Models\Family;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PopulationReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FamilyRelationshipRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PopulationReferenceSeeder::class);
    }

    public function test_spouse_relationship_is_rendered_as_wife(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();

        Livewire::actingAs($user)
            ->test(Families::class)
            ->assertSee('Istri')
            ->assertDontSee('Istri/Suami');
    }

    public function test_only_a_female_citizen_can_be_added_as_wife_to_a_male_headed_family(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        $family = Family::factory()->forTenant($tenant)->create();
        $head = Citizen::factory()->forTenant($tenant)->create([
            'jenis_kelamin' => 'male',
        ]);
        $wife = Citizen::factory()->forTenant($tenant)->create([
            'jenis_kelamin' => 'female',
        ]);
        $maleCitizen = Citizen::factory()->forTenant($tenant)->create([
            'jenis_kelamin' => 'male',
        ]);

        $family->update(['head_citizen_id' => $head->id]);

        Livewire::actingAs($user)
            ->test(Families::class)
            ->call('showDetail', $family->id)
            ->call('addMember', $family->id, $wife->id, 'spouse')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('family_members', [
            'family_id' => $family->id,
            'citizen_id' => $wife->id,
            'hubungan_dalam_keluarga' => 'spouse',
            'status' => 'active',
        ]);

        Livewire::actingAs($user)
            ->test(Families::class)
            ->call('showDetail', $family->id)
            ->call('addMember', $family->id, $maleCitizen->id, 'spouse')
            ->assertHasErrors('hubungan_dalam_keluarga');
    }
}
