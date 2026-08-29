<?php

namespace Database\Factories;

use App\Enums\PlatformRole;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
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
            'platform_role' => null,
            'custom_role_id' => null,
            'status' => UserStatus::ACTIVE,
            'tenant_id' => Tenant::factory(),
        ];
    }

    public function unverified(): static { return $this->state(fn(array $attributes) => ['email_verified_at' => null]); }

    public function superAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::SUPER_ADMIN,
            'platform_role' => PlatformRole::SUPER_ADMIN,
            'custom_role_id' => null,
            'tenant_id' => null,
        ]);
    }

    public function tenantAdmin(?Tenant $tenant = null): static
    {
        $tenant ??= Tenant::factory()->create();
        return $this->state(function (array $attributes) use ($tenant): array {
            $role = $this->ensureSystemRole('tenant_admin', 'Tenant Admin');
            $this->syncPermissions($role, ['dashboard.view','rbac.view','users.view','users.create','users.update','users.delete','tenant-users.view','tenant-profile.view','positions.view','positions.manage','letter-types.view','outgoing-letters.view','outgoing-letters.create','outgoing-letters.update','outgoing-letters.delete','outgoing-letters.submit','outgoing-letters.validate','outgoing-letters.reject','outgoing-letters.issue','outgoing-letters.withdraw']);
            return ['role' => UserRole::TENANT_ADMIN, 'platform_role' => null, 'custom_role_id' => $role->id, 'tenant_id' => $tenant->id];
        });
    }

    public function tenantUser(?Tenant $tenant = null): static
    {
        $tenant ??= Tenant::factory()->create();
        return $this->state(function (array $attributes) use ($tenant): array {
            $role = $this->ensureSystemRole('tenant_user', 'Tenant User');
            $this->syncPermissions($role, ['dashboard.view','tenant-profile.view','positions.view','letter-types.view','outgoing-letters.view','outgoing-letters.create','outgoing-letters.update','outgoing-letters.delete','outgoing-letters.submit','outgoing-letters.validate','outgoing-letters.reject','outgoing-letters.issue','outgoing-letters.withdraw']);
            return ['role' => UserRole::TENANT_USER, 'platform_role' => null, 'custom_role_id' => $role->id, 'tenant_id' => $tenant->id];
        });
    }

    private function ensureSystemRole(string $slug, string $name): Role
    {
        return Role::query()->updateOrCreate(
            ['tenant_id' => null, 'slug' => $slug],
            ['name' => $name, 'scope' => 'tenant', 'is_system' => true, 'is_active' => true],
        );
    }

    private function syncPermissions(Role $role, array $permissionSlugs): void
    {
        $permissionIds = Permission::query()->whereIn('slug', $permissionSlugs)->pluck('id');
        if ($permissionIds->isNotEmpty()) $role->permissions()->syncWithoutDetaching($permissionIds);
    }

    public function inactive(): static { return $this->state(fn(array $attributes) => ['status' => UserStatus::INACTIVE]); }
}
