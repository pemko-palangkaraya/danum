<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenantIds = DB::table('users')->whereNotNull('tenant_id')->distinct()->pluck('tenant_id');
        $permissions = [
            'tenant_admin' => ['dashboard.view', 'rbac.view', 'users.view', 'users.create', 'users.update', 'tenant-users.view', 'tenant-profile.view', 'positions.view', 'positions.manage', 'letter-types.view', 'outgoing-letters.view', 'outgoing-letters.create', 'outgoing-letters.update', 'outgoing-letters.delete', 'outgoing-letters.submit', 'outgoing-letters.validate', 'outgoing-letters.reject', 'outgoing-letters.issue', 'outgoing-letters.withdraw'],
            'tenant_user' => ['dashboard.view', 'tenant-profile.view', 'positions.view', 'outgoing-letters.view', 'outgoing-letters.create', 'outgoing-letters.update', 'outgoing-letters.delete', 'outgoing-letters.submit', 'outgoing-letters.validate', 'outgoing-letters.reject', 'outgoing-letters.issue', 'outgoing-letters.withdraw'],
        ];

        $permissionIds = DB::table('permissions')->whereIn('slug', array_unique(array_merge(...array_values($permissions))))->pluck('id', 'slug');

        foreach ($tenantIds as $tenantId) {
            foreach (['tenant_user' => 'Tenant User', 'tenant_admin' => 'Tenant Administrator'] as $slug => $name) {
                $role = DB::table('roles')->where('tenant_id', $tenantId)->where('slug', $slug)->first();
                $roleId = $role?->id;

                if ($roleId === null) {
                    $roleId = DB::table('roles')->insertGetId([
                        'tenant_id' => $tenantId,
                        'name' => $name,
                        'slug' => $slug,
                        'scope' => 'tenant',
                        'is_system' => true,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                foreach ($permissions[$slug] as $permissionSlug) {
                    if (isset($permissionIds[$permissionSlug])) {
                        DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionIds[$permissionSlug]]);
                    }
                }

                if ($slug === 'tenant_user') {
                    DB::table('users')->where('tenant_id', $tenantId)->where('role', 'tenant_user')->whereNull('custom_role_id')->update(['custom_role_id' => $roleId]);
                }
            }
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')->whereIn('slug', ['tenant_user', 'tenant_admin'])->where('is_system', true)->pluck('id');
        DB::table('role_permissions')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('id', $roleIds)->delete();
    }
};
