<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\TenantCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'tenant_category_id' => TenantCategory::query()->where('code', 'lainnya')->value('id'),
            'code' => fake()->unique()->bothify('POS-###'),
            'name' => fake()->jobTitle(),
            'description' => fake()->optional()->sentence(),
            'status' => PositionStatus::ACTIVE,
            'can_sign' => false,
            'can_validate' => false,
        ];
    }

    public function forCategory(TenantCategory $category): static
    {
        return $this->state(fn (): array => ['tenant_category_id' => $category->id]);
    }

    public function signatory(): static { return $this->state(fn (): array => ['can_sign' => true]); }
    public function validator(): static { return $this->state(fn (): array => ['can_validate' => true]); }
    public function inactive(): static { return $this->state(fn (): array => ['status' => PositionStatus::INACTIVE]); }
}
