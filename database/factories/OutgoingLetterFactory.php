<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\LetterType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutgoingLetterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'letter_type_id' => function (array $attributes): string {
                return LetterType::factory()->create([
                    'tenant_id' => $attributes['tenant_id'],
                    'status' => LetterTypeStatus::ACTIVE,
                ])->id;
            },
            'number' => fake()->unique()->bothify('###/SK/####'),
            'recipient_name' => fake()->name(),
            'recipient_address' => fake()->optional()->address(),
            'subject' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'issued_at' => null,
            'status' => OutgoingLetterStatus::DRAFT,
        ];
    }
}
