<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LetterClassification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LetterClassification>
 */
class LetterClassificationFactory extends Factory
{
    protected $model = LetterClassification::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('###.##'),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}/{{year}}',
            'number_padding' => 3,
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
