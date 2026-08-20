<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    public function test_user_status_contains_expected_cases(): void
    {
        $this->assertSame(
            'active',
            UserStatus::ACTIVE->value
        );

        $this->assertSame(
            'inactive',
            UserStatus::INACTIVE->value
        );
    }

    public function test_user_status_can_be_created_from_value(): void
    {
        $this->assertSame(
            UserStatus::ACTIVE,
            UserStatus::from('active')
        );

        $this->assertSame(
            UserStatus::INACTIVE,
            UserStatus::from('inactive')
        );
    }

    public function test_user_status_has_exactly_two_cases(): void
    {
        $this->assertCount(
            2,
            UserStatus::cases()
        );
    }
}
