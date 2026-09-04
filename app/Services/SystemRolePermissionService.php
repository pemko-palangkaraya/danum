<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;

final class SystemRolePermissionService
{
    /**
     * Canonical permissions for the built-in tenant roles.
     * Super Admin is intentionally excluded because it bypasses role grants.
     *
     * @var array<string, list<string>>
     */
    private const DEFAULTS = [
        'tenant_admin' => [
            'dashboard.view',
            'rbac.view',
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

    public function sync(Role $role): void
    {
        $slugs = self::DEFAULTS[$role->slug] ?? null;

        if ($slugs === null) {
            return;
        }

        $permissionIds = Permission::query()
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
    }
}
