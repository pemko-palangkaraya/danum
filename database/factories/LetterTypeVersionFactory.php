<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LetterType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\LetterTypeVersion>
 */
class LetterTypeVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'letter_type_id' => LetterType::factory(),
            'version' => 1,
            'body_template' => 'Nomor: {{number}}\nKepada: {{recipient_name}}\nPerihal: {{subject}}',
            'template_path' => null,
            'effective_from' => now(),
            'effective_until' => null,
            'is_active' => true,
            'change_note' => null,
            'created_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function effectiveFrom(\DateTimeInterface|string $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'effective_from' => $date,
        ]);
    }

    public function effectiveUntil(\DateTimeInterface|string $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'effective_until' => $date,
        ]);
    }
}
