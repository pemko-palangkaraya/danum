<?php

namespace Database\Factories;

use App\Enums\LetterTypeStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LetterType>
 */
class LetterTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'code' => fake()->unique()->regexify('[A-Z]{2,5}'),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => LetterTypeStatus::DRAFT,
        ];
    }
}
