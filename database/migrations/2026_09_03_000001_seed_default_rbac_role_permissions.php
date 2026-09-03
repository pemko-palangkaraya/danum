<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Default permission matrix for the built-in tenant roles.
     * Super Admin is intentionally omitted because User::hasPermission()
     * grants it full access without requiring pivot rows.
     *
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        'tenant_admin' => [
            'dashboard.view',
            'rbac.view',
            'rbac.manage',
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
        $roles = DB::table('roles')
            ->whereIn('slug', array_keys(self::ROLE_PERMISSIONS))
            ->whereNull('tenant_id')
            ->pluck('id', 'slug');

        $permissionSlugs = array_values(array_unique(array_merge(...array_values(self::ROLE_PERMISSIONS))));
        $permissions = DB::table('permissions')
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id', 'slug');

        $rows = [];
        foreach (self::ROLE_PERMISSIONS as $roleSlug => $slugs) {
            $roleId = $roles->get($roleSlug);
            if ($roleId === null) {
                continue;
            }

            foreach ($slugs as $slug) {
                $permissionId = $permissions->get($slug);
                if ($permissionId !== null) {
                    $rows[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ];
                }
            }
        }

        if ($rows !== []) {
            DB::table('role_permissions')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        $roles = DB::table('roles')
            ->whereIn('slug', array_keys(self::ROLE_PERMISSIONS))
            ->whereNull('tenant_id')
            ->pluck('id');

        $permissionSlugs = array_values(array_unique(array_merge(...array_values(self::ROLE_PERMISSIONS))));
        $permissions = DB::table('permissions')
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id');

        if ($roles->isNotEmpty() && $permissions->isNotEmpty()) {
            DB::table('role_permissions')
                ->whereIn('role_id', $roles)
                ->whereIn('permission_id', $permissions)
                ->delete();
        }
    }
};
