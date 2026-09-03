<?php

namespace Database\Seeders;

use App\Enums\PlatformRole;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
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

        $tenantCategoryId = TenantCategory::query()->where('code', 'lainnya')->value('id');
        if ($tenantCategoryId === null) {
            throw new \RuntimeException('Master tenant category "lainnya" tidak ditemukan.');
        }

        $tenant = Tenant::firstOrCreate(['code' => env('DANUM_DEMO_TENANT_CODE', 'DEMO001')], [
            'name' => env('DANUM_DEMO_TENANT_NAME', 'Demo Tenant'),
            'tenant_category_id' => $tenantCategoryId,
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Demo',
            'village' => 'Demo',
            'address' => 'Demo',
            'phone' => null,
            'email' => 'demo@example.com',
            'head_name' => 'Demo Head',
            'head_title' => 'Kepala Unit',
            'status' => TenantStatus::ACTIVE,
        ]);

        $tenantAdminRole = Role::query()->updateOrCreate(['tenant_id' => null, 'slug' => 'tenant_admin'], [
            'name' => 'Tenant Admin',
            'scope' => 'tenant',
            'is_system' => true,
            'is_active' => true,
        ]);

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

        $tenantUserRole = Role::query()->updateOrCreate(['tenant_id' => null, 'slug' => 'tenant_user'], [
            'name' => 'Tenant User',
            'scope' => 'tenant',
            'is_system' => true,
            'is_active' => true,
        ]);

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

        $this->call([
            PopulationReferenceSeeder::class,
            PositionSeeder::class,
            KalimantanTengahTenantSeeder::class,
        ]);
    }
}
