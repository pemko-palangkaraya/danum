<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyRoles = DB::table('roles')->whereIn('slug', ['tenant_user', 'tenant_admin'])->get()->keyBy('slug');
        $tenantIds = DB::table('users')->whereNotNull('tenant_id')->distinct()->pluck('tenant_id');

        foreach ($tenantIds as $tenantId) {
            foreach (['tenant_user' => 'Tenant User', 'tenant_admin' => 'Tenant Administrator'] as $slug => $name) {
                $existing = DB::table('roles')->where('tenant_id', $tenantId)->where('slug', $slug)->first();
                if ($existing) continue;

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

                $legacy = $legacyRoles->get($slug);
                if ($legacy) {
                    $permissions = DB::table('role_permissions')->where('role_id', $legacy->id)->get();
                    foreach ($permissions as $permission) {
                        DB::table('role_permissions')->insertOrIgnore([
                            'role_id' => $roleId,
                            'permission_id' => $permission->permission_id,
                        ]);
                    }
                }

                if ($slug === 'tenant_user') {
                    DB::table('users')
                        ->where('tenant_id', $tenantId)
                        ->where('role', 'tenant_user')
                        ->whereNull('custom_role_id')
                        ->update(['custom_role_id' => $roleId]);
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
