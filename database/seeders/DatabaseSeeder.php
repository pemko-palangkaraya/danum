<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PlatformRole;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantCategory;
use App\Models\User;
use App\Services\SystemRolePermissionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Reference/master data must exist before demo tenants depend on it.
        $this->call([
            TenantReferenceSeeder::class,
            PopulationReferenceSeeder::class,
            PositionSeeder::class,
            KalimantanTengahTenantSeeder::class,
        ]);

        $superAdminEmail = env('DANUM_SUPER_ADMIN_EMAIL', 'admin@danum.local');
        $superAdminPassword = env('DANUM_SUPER_ADMIN_PASSWORD', 'password');

        User::updateOrCreate(['email' => $superAdminEmail], [
            'name' => env('DANUM_SUPER_ADMIN_NAME', 'Super Admin'),
            'nip' => null,
            'email_verified_at' => now(),
            'password' => Hash::make($superAdminPassword),
            'remember_token' => null,
            'platform_role' => PlatformRole::SUPER_ADMIN,
            'custom_role_id' => null,
            'status' => UserStatus::ACTIVE,
            'tenant_id' => null,
        ]);

        $tenantCategoryId = TenantCategory::query()->where('code', 'kelurahan')->value('id');
        if ($tenantCategoryId === null) {
            throw new \RuntimeException('Master tenant category "kelurahan" tidak ditemukan.');
        }

        $parentTenantId = Tenant::query()
            ->where('code', 'wilayah-62-71-04')
            ->whereHas('category', fn ($query) => $query->where('code', 'kecamatan'))
            ->value('id');

        if ($parentTenantId === null) {
            throw new \RuntimeException('Tenant Kecamatan Rakumpit belum tersedia untuk parent demo tenant.');
        }

        $tenant = Tenant::updateOrCreate(
            ['code' => env('DANUM_DEMO_TENANT_CODE', 'DEMO001')],
            [
                'name' => env('DANUM_DEMO_TENANT_NAME', 'Demo Tenant - Kelurahan Mungku Baru'),
                'tenant_category_id' => $tenantCategoryId,
                'parent_tenant_id' => $parentTenantId,
                'province' => 'Kalimantan Tengah',
                'city' => 'Palangka Raya',
                'district' => 'Rakumpit',
                'village' => 'Mungku Baru',
                'address' => 'Kelurahan Mungku Baru, Kecamatan Rakumpit, Palangka Raya',
                'phone' => null,
                'email' => 'demo@example.com',
                'head_name' => 'Demo Head',
                'head_title' => 'Lurah',
                'status' => TenantStatus::ACTIVE,
            ],
        );

        $permissionService = app(SystemRolePermissionService::class);

        $tenantAdminRole = $this->ensureSystemRole('tenant_admin', 'Tenant Admin');
        $permissionService->sync($tenantAdminRole);

        User::updateOrCreate(['email' => env('DANUM_TENANT_ADMIN_EMAIL', 'yudhistira@danum.local')], [
            'name' => 'Tenant Admin',
            'nip' => null,
            'email_verified_at' => now(),
            'password' => Hash::make(env('DANUM_TENANT_ADMIN_PASSWORD', 'password')),
            'remember_token' => null,
            'platform_role' => null,
            'custom_role_id' => $tenantAdminRole->id,
            'status' => UserStatus::ACTIVE,
            'tenant_id' => $tenant->id,
        ]);

        $tenantUserRole = $this->ensureSystemRole('tenant_user', 'Tenant User');
        $permissionService->sync($tenantUserRole);

        User::updateOrCreate(['email' => env('DANUM_TENANT_USER_EMAIL', 'ucok@danum.local')], [
            'name' => 'Tenant User',
            'nip' => null,
            'email_verified_at' => now(),
            'password' => Hash::make(env('DANUM_TENANT_USER_PASSWORD', 'password')),
            'remember_token' => null,
            'platform_role' => null,
            'custom_role_id' => $tenantUserRole->id,
            'status' => UserStatus::ACTIVE,
            'tenant_id' => $tenant->id,
        ]);
    }

    private function ensureSystemRole(string $slug, string $name): Role
    {
        return Role::query()->updateOrCreate(
            ['tenant_id' => null, 'slug' => $slug],
            [
                'name' => $name,
                'scope' => 'tenant',
                'is_system' => true,
                'is_active' => true,
            ],
        );
    }
}
