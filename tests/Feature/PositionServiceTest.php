<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PositionStatus;
use App\Enums\UserStatus;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\PositionHolderRepositoryInterface;
use App\Repositories\Contracts\PositionRepositoryInterface;
use App\Services\PositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class PositionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PositionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PositionService::class);
    }

    public function test_it_can_find_position(): void
    {
        $position = Position::factory()->create();

        $result = $this->service->find($position->id);

        $this->assertNotNull($result);
        $this->assertTrue($result->is($position));
    }

    public function test_it_can_find_position_by_code_within_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'LURAH',
        ]);

        $result = $this->service->findByCode(
            $tenant->id,
            'LURAH'
        );

        $this->assertTrue($result->is($position));
    }

    public function test_it_can_get_all_positions_for_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        Position::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
        ]);

        $result = $this->service->getAll($tenant->id);

        $this->assertCount(3, $result);
    }

    public function test_it_can_create_position(): void
    {
        $tenant = Tenant::factory()->create();

        $position = $this->service->create([
            'tenant_id' => $tenant->id,
            'code' => 'LURAH',
            'name' => 'Lurah',
            'description' => null,
            'status' => PositionStatus::ACTIVE,
        ]);

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'tenant_id' => $tenant->id,
            'code' => 'LURAH',
        ]);
    }

    public function test_updating_position_to_inactive_ends_active_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        $before = now()->startOfSecond();

        $this->service->update($position, [
            'status' => PositionStatus::INACTIVE,
        ]);

        $position->refresh();
        $holder->refresh();

        $this->assertSame(
            PositionStatus::INACTIVE,
            $position->status
        );

        $this->assertNotNull($holder->ended_at);
        $this->assertGreaterThanOrEqual(
            $before,
            $holder->ended_at
        );
    }

    public function test_updating_position_without_status_change_does_not_end_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        $this->service->update($position, [
            'name' => 'Lurah Baru',
        ]);

        $holder->refresh();

        $this->assertNull($holder->ended_at);
    }

    public function test_it_can_end_active_holder(): void
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

        $result = $this->service->endHolder(
            $holder,
            $endedAt
        );

        $this->assertNotNull($result->ended_at);
    }

    public function test_it_rejects_ending_holder_before_started_at(): void
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
            'started_at' => now()->subDay(),
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->endHolder(
            $holder,
            now()->subDays(2)
        );
    }

    public function test_it_can_get_active_holder(): void
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

        $result = $this->service->getActiveHolder($position);

        $this->assertNotNull($result);
        $this->assertTrue($result->is($holder));
    }

    public function test_it_can_get_holder_history(): void
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

        $result = $this->service->getHolderHistory($position);

        $this->assertCount(2, $result);
    }

    public function test_it_can_assign_active_user_from_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create([
                'status' => UserStatus::ACTIVE,
            ]);

        $startedAt = now()->startOfSecond();

        $holder = $this->service->assignHolder(
            $position,
            $user->id,
            $startedAt
        );

        $this->assertSame(
            $position->id,
            $holder->position_id
        );

        $this->assertSame(
            $user->id,
            $holder->user_id
        );

        $this->assertEquals(
            $startedAt,
            $holder->started_at
        );

        $this->assertNull($holder->ended_at);
    }

    public function test_inactive_user_cannot_become_position_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->inactive()
            ->create();

        $this->expectException(LogicException::class);

        $this->service->assignHolder(
            $position,
            $user->id,
            now()->startOfSecond()
        );
    }

    public function test_user_from_different_tenant_cannot_become_position_holder(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenantA->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $user = User::factory()
            ->tenantUser($tenantB)
            ->create();

        $this->expectException(LogicException::class);

        $this->service->assignHolder(
            $position,
            $user->id,
            now()->startOfSecond()
        );
    }

    public function test_inactive_position_cannot_have_new_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()
            ->inactive()
            ->create([
                'tenant_id' => $tenant->id,
            ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->expectException(LogicException::class);

        $this->service->assignHolder(
            $position,
            $user->id,
            now()->startOfSecond()
        );
    }

    public function test_assigning_new_holder_ends_previous_holder_at_new_start_date(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $oldUser = User::factory()
            ->tenantUser($tenant)
            ->create();

        $newUser = User::factory()
            ->tenantUser($tenant)
            ->create();

        $oldStartedAt = now()
            ->subDays(10)
            ->startOfSecond();

        $newStartedAt = now()
            ->startOfSecond();

        $oldHolder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $oldUser->id,
            'started_at' => $oldStartedAt,
            'ended_at' => null,
        ]);

        $newHolder = $this->service->assignHolder(
            $position,
            $newUser->id,
            $newStartedAt
        );

        $oldHolder->refresh();

        $this->assertEquals(
            $newStartedAt,
            $oldHolder->ended_at
        );

        $this->assertEquals(
            $newStartedAt,
            $newHolder->started_at
        );

        $this->assertNull($newHolder->ended_at);
    }

    public function test_new_holder_cannot_start_before_current_holder_started_at(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $oldUser = User::factory()
            ->tenantUser($tenant)
            ->create();

        $newUser = User::factory()
            ->tenantUser($tenant)
            ->create();

        $currentStartedAt = now()
            ->startOfSecond();

        PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $oldUser->id,
            'started_at' => $currentStartedAt,
            'ended_at' => null,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->assignHolder(
            $position,
            $newUser->id,
            $currentStartedAt->copy()->subDay()
        );
    }
}
