<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\PositionHolderRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionHolderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PositionHolderRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(
            PositionHolderRepositoryInterface::class
        );
    }

    public function test_it_can_find_position_holder_by_id(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        $result = $this->repository->find($holder->id);

        $this->assertNotNull($result);
        $this->assertTrue($result->is($holder));
    }

    public function test_it_can_get_position_holder_history(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $userA = User::factory()
            ->tenantUser($tenant)
            ->create();

        $userB = User::factory()
            ->tenantUser($tenant)
            ->create();

        PositionHolder::factory()->ended()->create([
            'position_id' => $position->id,
            'user_id' => $userA->id,
        ]);

        PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $userB->id,
        ]);

        $result = $this->repository->getHistory(
            $position->id
        );

        $this->assertCount(2, $result);
    }

    public function test_it_can_find_active_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        $result = $this->repository->findActive(
            $position->id
        );

        $this->assertNotNull($result);
        $this->assertTrue($result->is($holder));
    }

    public function test_it_returns_null_when_position_has_no_active_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        PositionHolder::factory()->ended()->create([
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        $result = $this->repository->findActive(
            $position->id
        );

        $this->assertNull($result);
    }

    public function test_it_can_create_position_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $startedAt = now();

        $holder = $this->repository->create([
            'position_id' => $position->id,
            'user_id' => $user->id,
            'started_at' => $startedAt,
            'ended_at' => null,
        ]);

        $this->assertInstanceOf(
            PositionHolder::class,
            $holder
        );

        $this->assertDatabaseHas('position_holders', [
            'id' => $holder->id,
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_it_can_end_position_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        $endedAt = now();

        $result = $this->repository->end(
            $holder,
            $endedAt
        );

        $this->assertNotNull($result->ended_at);

        $this->assertDatabaseHas('position_holders', [
            'id' => $holder->id,
        ]);
    }

    public function test_it_can_get_all_active_holders_by_user_id(): void
    {
        $tenant = Tenant::factory()->create();

        $positionA = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $positionB = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        PositionHolder::factory()->create([
            'position_id' => $positionA->id,
            'user_id' => $user->id,
            'ended_at' => null,
        ]);

        PositionHolder::factory()->create([
            'position_id' => $positionB->id,
            'user_id' => $user->id,
            'ended_at' => null,
        ]);

        $result = $this->repository->getActiveByUserId($user->id);

        $this->assertCount(2, $result);
    }
}
