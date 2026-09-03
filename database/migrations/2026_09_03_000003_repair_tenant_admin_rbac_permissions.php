<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'rbac.view',
        'rbac.manage',
    ];

    public function up(): void
    {
        $roleIds = DB::table('roles')
            ->where('slug', 'tenant_admin')
            ->where('is_system', true)
            ->whereNull('tenant_id')
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', self::PERMISSIONS)
            ->pluck('id');

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
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')
            ->where('slug', 'tenant_admin')
            ->where('is_system', true)
            ->whereNull('tenant_id')
            ->pluck('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', self::PERMISSIONS)
            ->pluck('id');

        if ($roleIds->isNotEmpty() && $permissionIds->isNotEmpty()) {
            DB::table('role_permissions')
                ->whereIn('role_id', $roleIds)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }
};
