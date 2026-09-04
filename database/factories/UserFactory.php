<?php

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
    protected static ?string $password;

    public function definition(): array
    {
        $tenant = Tenant::factory();
        $role = Role::resolveSystemForTenant('tenant_user', $tenant->getKey());

        return [
            'name' => fake()->name(),
            'nip' => null,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'platform_role' => null,
            'custom_role_id' => $role?->getKey(),
            'status' => UserStatus::ACTIVE,
            'tenant_id' => $tenant,
        ];
    }

    public function unverified(): static { return $this->state(fn(array $attributes) => ['email_verified_at' => null]); }

    public function superAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
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
            return ['platform_role' => null, 'custom_role_id' => $role->id, 'tenant_id' => $tenant->id];
        });
    }

    public function tenantUser(?Tenant $tenant = null): static
    {
        $tenant ??= Tenant::factory()->create();
        return $this->state(function (array $attributes) use ($tenant): array {
            $role = $this->ensureSystemRole('tenant_user', $tenant->id);
            app(SystemRolePermissionService::class)->sync($role);
            return ['platform_role' => null, 'custom_role_id' => $role->id, 'tenant_id' => $tenant->id];
        });
    }

    private function ensureSystemRole(string $slug, string|int $tenantId): Role
    {
        return Role::resolveSystemForTenant($slug, $tenantId) ?? throw new \RuntimeException("System role [{$slug}] tidak ditemukan.");
    }

    public function inactive(): static { return $this->state(fn(array $attributes) => ['status' => UserStatus::INACTIVE]); }
}
