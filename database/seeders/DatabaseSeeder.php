<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The RBAC tables are populated by their migration. This seeder adds a
     * deterministic local administrator so a fresh development database can
     * be used immediately from the UI.
     */
    public function run(): void
    {
        $superAdminEmail = env('DANUM_SUPER_ADMIN_EMAIL', 'superadmin@example.com');
        $superAdminPassword = env('DANUM_SUPER_ADMIN_PASSWORD', 'password');

        User::updateOrCreate(
            ['email' => $superAdminEmail],
            [
                'name' => env('DANUM_SUPER_ADMIN_NAME', 'Super Admin'),
                'nip' => null,
                'email_verified_at' => now(),
                'password' => Hash::make($superAdminPassword),
                'remember_token' => null,
                'role' => UserRole::SUPER_ADMIN,
                'status' => UserStatus::ACTIVE,
                'tenant_id' => null,
            ],
        );

        $tenant = Tenant::firstOrCreate(
            ['code' => env('DANUM_DEMO_TENANT_CODE', 'DEMO001')],
            [
                'name' => env('DANUM_DEMO_TENANT_NAME', 'Demo Tenant'),
                'province' => 'Kalimantan Tengah',
                'city' => 'Palangka Raya',
                'district' => 'Demo',
                'village' => 'Demo',
                'address' => 'Demo',
                'phone' => null,
                'email' => 'demo@example.com',
                'logo' => null,
                'head_name' => 'Demo Head',
                'head_title' => 'Kepala Unit',
                'status' => 'active',
            ],
        );

        User::updateOrCreate(
            ['email' => env('DANUM_TENANT_ADMIN_EMAIL', 'tenantadmin@example.com')],
            [
                'name' => 'Tenant Admin',
                'nip' => null,
                'email_verified_at' => now(),
                'password' => Hash::make(env('DANUM_TENANT_ADMIN_PASSWORD', 'password')),
                'remember_token' => null,
                'role' => UserRole::TENANT_ADMIN,
                'status' => UserStatus::ACTIVE,
                'tenant_id' => $tenant->id,
            ],
        );

        User::updateOrCreate(
            ['email' => env('DANUM_TENANT_USER_EMAIL', 'tenantuser@example.com')],
            [
                'name' => 'Tenant User',
                'nip' => null,
                'email_verified_at' => now(),
                'password' => Hash::make(env('DANUM_TENANT_USER_PASSWORD', 'password')),
                'remember_token' => null,
                'role' => UserRole::TENANT_USER,
                'status' => UserStatus::ACTIVE,
                'tenant_id' => $tenant->id,
            ],
        );
    }
}
