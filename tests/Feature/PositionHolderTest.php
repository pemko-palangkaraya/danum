<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionHolderTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_holder_can_be_created(): void
    {
        $tenant = Tenant::factory()->create();
        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('position_holders', [
            'id' => $holder->id,
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_position_holder_belongs_to_position(): void
    {
        $tenant = Tenant::factory()->create();
        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
        ]);

        $this->assertTrue(
            $holder->position->is($position)
        );
    }

    public function test_position_holder_belongs_to_user(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $holder = PositionHolder::factory()->create([
            'position_id' => Position::factory()->create([
                'tenant_id' => $tenant->id,
            ])->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            $holder->user->is($user)
        );
    }
}
