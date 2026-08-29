<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('platform_role')->nullable()->after('role');
        });

        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['platform_role' => 'super_admin']);

        $legacyTenantAdmin = DB::table('roles')->where('slug', 'tenant_admin')->first();

        if ($legacyTenantAdmin !== null) {
            $permissionIds = DB::table('role_permissions')
                ->where('role_id', $legacyTenantAdmin->id)
                ->pluck('permission_id');

            $tenantIds = DB::table('users')
                ->where('role', 'tenant_admin')
                ->whereNotNull('tenant_id')
                ->distinct()
                ->pluck('tenant_id');

            foreach ($tenantIds as $tenantId) {
                $roleId = DB::table('roles')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => 'Tenant Administrator',
                    'slug' => 'tenant_admin_' . $tenantId,
                    'scope' => 'tenant',
                    'is_system' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($permissionIds as $permissionId) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }

                DB::table('users')
                    ->where('role', 'tenant_admin')
                    ->where('tenant_id', $tenantId)
                    ->update(['custom_role_id' => $roleId]);
            }
        }

        DB::table('users')
            ->where('platform_role', 'super_admin')
            ->update(['tenant_id' => null, 'custom_role_id' => null]);

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique('roles_slug_unique');
            $table->unique(['tenant_id', 'slug']);
        });

        DB::table('roles')
            ->where('is_system', true)
            ->whereIn('slug', ['super_admin', 'tenant_admin', 'tenant_user'])
            ->delete();
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'like', 'tenant_admin_%')->where('is_system', true)->delete();

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'slug']);
            $table->unique('slug');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('platform_role');
        });
    }
};
