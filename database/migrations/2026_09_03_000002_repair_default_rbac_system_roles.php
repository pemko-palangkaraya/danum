<?php

declare(strict_types=1);

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ROLE_PERMISSIONS = [
        UserRole::TENANT_ADMIN->value => [
            'dashboard.view',
            'tenant-users.view',
            'tenant-profile.view',
            'positions.view',
            'positions.manage',
            'letter-types.view',
            'letter-types.manage',
            'letter-types.permissions',
            'letter-types.versions',
            'outgoing-letters.view',
            'outgoing-letters.create',
            'outgoing-letters.update',
            'outgoing-letters.delete',
            'outgoing-letters.submit',
            'outgoing-letters.validate',
            'outgoing-letters.reject',
            'outgoing-letters.issue',
            'outgoing-letters.withdraw',
            'population.view',
            'population.manage',
        ],
        UserRole::TENANT_USER->value => [
            'dashboard.view',
            'tenant-profile.view',
            'positions.view',
            'letter-types.view',
            'outgoing-letters.view',
            'outgoing-letters.create',
            'outgoing-letters.update',
            'outgoing-letters.delete',
            'outgoing-letters.submit',
            'population.view',
        ],
    ];

    public function up(): void
    {
        foreach (self::ROLE_PERMISSIONS as $roleSlug => $permissionSlugs) {
            $roleIds = DB::table('roles')
                ->where('slug', $roleSlug)
                ->where('is_system', true)
                ->whereNull('tenant_id')
                ->pluck('id');

            if ($roleIds->isEmpty()) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('slug', $permissionSlugs)
                ->pluck('id');

            DB::table('role_permissions')->whereIn('role_id', $roleIds)->delete();

            $rows = [];
            foreach ($roleIds as $roleId) {
                foreach ($permissionIds as $permissionId) {
                    $rows[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ];
                }
            }

            if ($rows !== []) {
                DB::table('role_permissions')->insertOrIgnore($rows);
            }

            $canonicalRoleId = $roleIds->first();

            DB::table('users')
                ->where('role', $roleSlug)
                ->whereNotNull('tenant_id')
                ->whereNull('custom_role_id')
                ->update(['custom_role_id' => $canonicalRoleId]);
        }
    }

    public function down(): void
    {
        // Permission repair is intentionally not reversed; the previous state
        // may contain stale or unsafe grants and should not be restored.
    }
};
