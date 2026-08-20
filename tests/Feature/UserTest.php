<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_status_defaults_to_active(): void
    {
        $user = User::factory()->create();

        $this->assertSame(
            UserStatus::ACTIVE,
            $user->status
        );
    }

    public function test_user_can_be_inactive(): void
    {
        $user = User::factory()
            ->inactive()
            ->create();

        $this->assertSame(
            UserStatus::INACTIVE,
            $user->status
        );
    }
}
