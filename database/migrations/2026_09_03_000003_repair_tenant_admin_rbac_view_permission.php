<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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

        $viewPermissionId = DB::table('permissions')
            ->where('slug', 'rbac.view')
            ->value('id');

        if ($viewPermissionId !== null) {
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $viewPermissionId,
                ]);
            }
        }

        $managePermissionId = DB::table('permissions')
            ->where('slug', 'rbac.manage')
            ->value('id');

        if ($managePermissionId !== null) {
            DB::table('role_permissions')
                ->whereIn('role_id', $roleIds)
                ->where('permission_id', $managePermissionId)
                ->delete();
        }
    }

    public function down(): void
    {
        // Intentionally left empty; the canonical role matrix remains view-only.
    }
};
