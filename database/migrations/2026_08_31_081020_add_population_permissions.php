<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            ['name' => 'Population View', 'slug' => 'population.view', 'module' => 'population', 'action' => 'view'],
            ['name' => 'Population Manage', 'slug' => 'population.manage', 'module' => 'population', 'action' => 'manage'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission + ['scope' => 'tenant', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        $tenantAdmin = DB::table('roles')->where('slug', 'tenant_admin')->whereNull('tenant_id')->value('id');
        $tenantUser = DB::table('roles')->where('slug', 'tenant_user')->whereNull('tenant_id')->value('id');
        $view = DB::table('permissions')->where('slug', 'population.view')->value('id');
        $manage = DB::table('permissions')->where('slug', 'population.manage')->value('id');

        if ($tenantAdmin && $view) {
            DB::table('role_permissions')->updateOrInsert(['role_id' => $tenantAdmin, 'permission_id' => $view], []);
        }
        if ($tenantAdmin && $manage) {
            DB::table('role_permissions')->updateOrInsert(['role_id' => $tenantAdmin, 'permission_id' => $manage], []);
        }
        if ($tenantUser && $view) {
            DB::table('role_permissions')->updateOrInsert(['role_id' => $tenantUser, 'permission_id' => $view], []);
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('slug', ['population.view', 'population.manage'])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
