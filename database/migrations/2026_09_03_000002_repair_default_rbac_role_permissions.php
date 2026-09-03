<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Keep the built-in tenant roles deterministic even when an existing
     * installation already contains role_permission rows from older RBAC code.
     *
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        'tenant_admin' => [
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
        'tenant_user' => [
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
            $roleId = DB::table('roles')
                ->where('slug', $roleSlug)
                ->whereNull('tenant_id')
                ->where('is_system', true)
                ->value('id');

            if ($roleId === null) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('slug', $permissionSlugs)
                ->pluck('id', 'slug');

            // Remove stale permissions from the built-in role first so the
            // matrix is an exact allow-list, not an additive patch.
            DB::table('role_permissions')->where('role_id', $roleId)->delete();

            $rows = [];
            foreach ($permissionSlugs as $slug) {
                $permissionId = $permissionIds->get($slug);
                if ($permissionId !== null) {
                    $rows[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ];
                }
            }

            if ($rows !== []) {
                DB::table('role_permissions')->insert($rows);
            }
        }

        // Repair users created by older versions which still have the enum
        // role but no custom_role_id. This makes their authorization resolve
        // to the same deterministic system role immediately after migration.
        $systemRoles = DB::table('roles')
            ->whereIn('slug', array_keys(self::ROLE_PERMISSIONS))
            ->whereNull('tenant_id')
            ->where('is_system', true)
            ->pluck('id', 'slug');

        foreach ($systemRoles as $slug => $roleId) {
            DB::table('users')
                ->where('role', $slug)
                ->whereNull('custom_role_id')
                ->update(['custom_role_id' => $roleId]);
        }
    }

    public function down(): void
    {
        foreach (self::ROLE_PERMISSIONS as $roleSlug => $permissionSlugs) {
            $roleId = DB::table('roles')
                ->where('slug', $roleSlug)
                ->whereNull('tenant_id')
                ->where('is_system', true)
                ->value('id');

            if ($roleId === null) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('slug', $permissionSlugs)
                ->pluck('id');

            DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }
};
