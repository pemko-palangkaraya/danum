<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PositionHolder>
 */
class PositionHolderFactory extends Factory
{
    protected $model = PositionHolder::class;

    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'position_id' => Position::factory(),
            'user_id' => User::factory(),
            'started_at' => $startedAt,
            'ended_at' => null,
        ];
    }

    public function ended(): static
    {
        return $this->state(function (): array {
            $startedAt = fake()->dateTimeBetween('-1 year', '-1 day');

            return [
                'started_at' => $startedAt,
                'ended_at' => fake()->dateTimeBetween(
                    $startedAt,
                    'now'
                ),
            ];
        });
    }
}
