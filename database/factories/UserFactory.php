<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nip' => null,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
            'tenant_id' => Tenant::factory(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::SUPER_ADMIN,
            'tenant_id' => null,
        ]);
    }

    public function tenantAdmin(?Tenant $tenant = null): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::TENANT_ADMIN,
            'tenant_id' => $tenant?->id ?? Tenant::factory(),
        ]);
    }

    public function tenantUser(?Tenant $tenant = null): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::TENANT_USER,
            'tenant_id' => $tenant?->id ?? Tenant::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => UserStatus::INACTIVE,
        ]);
    }
}
