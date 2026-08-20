<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_can_be_created(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'tenant_id' => $tenant->id,
            'code' => $position->code,
            'name' => $position->name,
            'status' => PositionStatus::ACTIVE->value,
        ]);
    }

    public function test_position_status_is_cast_to_enum(): void
    {
        $position = Position::factory()->create([
            'status' => PositionStatus::ACTIVE,
        ]);

        $this->assertInstanceOf(
            PositionStatus::class,
            $position->status
        );

        $this->assertSame(
            PositionStatus::ACTIVE,
            $position->status
        );
    }

    public function test_position_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue(
            $position->tenant->is($tenant)
        );
    }
}
