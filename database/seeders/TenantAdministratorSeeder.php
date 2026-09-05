<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SystemRolePermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class TenantAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::query()->updateOrCreate(
            ['tenant_id' => null, 'slug' => 'tenant_admin'],
            [
                'name' => 'Tenant Admin',
                'scope' => 'tenant',
                'is_system' => true,
                'is_active' => true,
            ],
        );

        app(SystemRolePermissionService::class)->sync($role);

        $password = env('DANUM_SEEDED_TENANT_ADMIN_PASSWORD', 'password');

        Tenant::query()
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->each(function (Tenant $tenant) use ($role, $password): void {
                $administrator = $tenant->administrator;

                if ($administrator !== null) {
                    return;
                }

                $email = sprintf('%s@danum.local', $tenant->code);
                $existing = User::query()->where('email', $email)->first();

                if ($existing !== null && $existing->tenant_id !== $tenant->id) {
                    throw new RuntimeException(
                        "Email administrator {$email} sudah digunakan oleh tenant lain."
                    );
                }

                $administrator = $existing ?? User::query()->create([
                    'name' => 'Tenant Admin - '.$tenant->name,
                    'nip' => null,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => Hash::make($password),
                    'remember_token' => null,
                    'platform_role' => null,
                    'custom_role_id' => $role->id,
                    'status' => UserStatus::ACTIVE,
                    'tenant_id' => $tenant->id,
                ]);

                $tenant->forceFill([
                    'administrator_user_id' => $administrator->id,
                ])->save();
            });
    }
}
