<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlatformRole;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SystemRolePermissionService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nip' => null,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'platform_role' => null,
            'custom_role_id' => null,
            'status' => UserStatus::ACTIVE,
            'tenant_id' => Tenant::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            if ($user->platform_role !== null || $user->tenant_id === null || $user->custom_role_id !== null) {
                return;
            }

            $role = Role::resolveSystemForTenant('tenant_user', $user->tenant_id);
            if ($role === null) {
                throw new \RuntimeException('System role [tenant_user] tidak ditemukan.');
            }

            app(SystemRolePermissionService::class)->sync($role);
            $user->forceFill(['custom_role_id' => $role->id])->saveQuietly();
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => ['email_verified_at' => null]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform_role' => PlatformRole::SUPER_ADMIN,
            'custom_role_id' => null,
            'tenant_id' => null,
        ]);
    }

    public function tenantAdmin(?Tenant $tenant = null): static
    {
        $tenant ??= Tenant::factory()->create();

        return $this->state(function (array $attributes) use ($tenant): array {
            $role = $this->ensureSystemRole('tenant_admin', $tenant->id);
            app(SystemRolePermissionService::class)->sync($role);

            return [
                'platform_role' => null,
                'custom_role_id' => $role->id,
                'tenant_id' => $tenant->id,
            ];
        });
    }

    public function tenantUser(?Tenant $tenant = null): static
    {
        $tenant ??= Tenant::factory()->create();

        return $this->state(function (array $attributes) use ($tenant): array {
            $role = $this->ensureSystemRole('tenant_user', $tenant->id);
            app(SystemRolePermissionService::class)->sync($role);

            return [
                'platform_role' => null,
                'custom_role_id' => $role->id,
                'tenant_id' => $tenant->id,
            ];
        });
    }

    private function ensureSystemRole(string $slug, string|int $tenantId): Role
    {
        return Role::resolveSystemForTenant($slug, $tenantId)
            ?? throw new \RuntimeException("System role [{$slug}] tidak ditemukan.");
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => UserStatus::INACTIVE]);
    }
}
