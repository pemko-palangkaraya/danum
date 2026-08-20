<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PositionStatus;
use Tests\TestCase;

class PositionStatusTest extends TestCase
{
    public function test_position_status_contains_expected_cases(): void
    {
        $this->assertSame(
            'active',
            PositionStatus::ACTIVE->value
        );

        $this->assertSame(
            'inactive',
            PositionStatus::INACTIVE->value
        );
    }

    public function test_position_status_can_be_created_from_value(): void
    {
        $this->assertSame(
            PositionStatus::ACTIVE,
            PositionStatus::from('active')
        );

        $this->assertSame(
            PositionStatus::INACTIVE,
            PositionStatus::from('inactive')
        );
    }

    public function test_position_status_has_exactly_two_cases(): void
    {
        $this->assertCount(
            2,
            PositionStatus::cases()
        );
    }
}
