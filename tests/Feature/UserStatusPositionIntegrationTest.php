<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PositionStatus;
use App\Enums\UserStatus;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusPositionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivating_user_ends_all_active_position_holders(): void
    {
        $tenant = Tenant::factory()->create();

        $positionA = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $positionB = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create([
                'status' => UserStatus::ACTIVE,
            ]);

        $holderA = PositionHolder::factory()->create([
            'position_id' => $positionA->id,
            'user_id' => $user->id,
            'ended_at' => null,
        ]);

        $holderB = PositionHolder::factory()->create([
            'position_id' => $positionB->id,
            'user_id' => $user->id,
            'ended_at' => null,
        ]);

        $changedAt = now()->startOfSecond();

        app(UserService::class)->update($user, [
            'status' => UserStatus::INACTIVE,
        ]);

        $holderA->refresh();
        $holderB->refresh();
        $user->refresh();

        $this->assertSame(
            UserStatus::INACTIVE,
            $user->status
        );

        $this->assertNotNull($holderA->ended_at);
        $this->assertNotNull($holderB->ended_at);

        $this->assertSame(
            $holderA->ended_at->format('Y-m-d H:i:s'),
            $holderB->ended_at->format('Y-m-d H:i:s')
        );
    }

    public function test_deactivating_user_does_not_affect_already_ended_holders(): void
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

        $holder = PositionHolder::factory()
            ->ended()
            ->create([
                'position_id' => $position->id,
                'user_id' => $user->id,
            ]);

        $originalEndedAt = $holder->ended_at;

        app(UserService::class)->update($user, [
            'status' => UserStatus::INACTIVE,
        ]);

        $holder->refresh();

        $this->assertEquals(
            $originalEndedAt,
            $holder->ended_at
        );
    }

    public function test_reactivating_user_does_not_end_position_holders(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create([
                'status' => UserStatus::INACTIVE,
            ]);

        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $user->id,
            'ended_at' => null,
        ]);

        app(UserService::class)->update($user, [
            'status' => UserStatus::ACTIVE,
        ]);

        $holder->refresh();

        $this->assertNull($holder->ended_at);
    }
}
