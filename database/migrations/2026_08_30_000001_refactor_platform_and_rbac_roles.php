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

        DB::table('users')->where('role', 'super_admin')->update([
            'platform_role' => 'super_admin', 'tenant_id' => null, 'custom_role_id' => null,
        ]);

        $legacyRoles = DB::table('roles')->whereIn('slug', ['tenant_admin', 'tenant_user'])->get();
        foreach ($legacyRoles as $legacyRole) {
            $tenantIds = DB::table('users')->where('role', $legacyRole->slug)->whereNotNull('tenant_id')->distinct()->pluck('tenant_id');
            foreach ($tenantIds as $tenantId) {
                $roleId = DB::table('roles')->where('tenant_id', $tenantId)->where('slug', $legacyRole->slug)->value('id');
                if ($roleId === null) {
                    $roleId = DB::table('roles')->insertGetId([
                        'tenant_id' => $tenantId, 'name' => $legacyRole->name, 'slug' => $legacyRole->slug,
                        'scope' => 'tenant', 'is_system' => (bool) $legacyRole->is_system, 'is_active' => (bool) $legacyRole->is_active,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                foreach (DB::table('role_permissions')->where('role_id', $legacyRole->id)->pluck('permission_id') as $permissionId) {
                    DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
                }
                DB::table('users')->where('role', $legacyRole->slug)->where('tenant_id', $tenantId)->update(['custom_role_id' => $roleId]);
            }
        }

        DB::table('roles')->whereIn('slug', ['super_admin', 'tenant_admin', 'tenant_user'])->whereNull('tenant_id')->delete();
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('slug', ['tenant_admin', 'tenant_user'])->where('is_system', true)->delete();
        Schema::table('users', function (Blueprint $table): void { $table->dropColumn('platform_role'); });
    }
};
