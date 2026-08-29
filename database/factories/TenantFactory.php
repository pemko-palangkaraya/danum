<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'name' => fake()->company(),
            'tenant_category_id' => TenantCategory::query()->where('code', 'lainnya')->value('id'),

            'province' => fake()->state(),
            'city' => fake()->city(),
            'district' => fake()->city(),
            'village' => fake()->city(),

            'address' => fake()->address(),

            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'logo' => null,

            'head_name' => fake()->name(),
            'head_title' => 'Kepala Kelurahan',

            'status' => TenantStatus::ACTIVE,
        ];
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => TenantStatus::INACTIVE,
        ]);
    }
}