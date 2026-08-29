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
        Schema::table('roles', function (Blueprint $table): void {
            $table->foreignUuid('tenant_id')->nullable()->change();
        });

        foreach ([
            'tenant_admin' => 'Tenant Admin',
            'tenant_user' => 'Tenant User',
        ] as $slug => $name) {
            $globalRoleId = DB::table('roles')
                ->whereNull('tenant_id')
                ->where('slug', $slug)
                ->value('id');

            if ($globalRoleId === null) {
                $globalRoleId = DB::table('roles')->insertGetId([
                    'tenant_id' => null,
                    'name' => $name,
                    'slug' => $slug,
                    'scope' => 'global',
                    'is_system' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $sourceRoleIds = DB::table('roles')
                ->where('slug', $slug)
                ->where('is_system', true)
                ->whereNotNull('tenant_id')
                ->pluck('id');

            foreach ($sourceRoleIds as $sourceRoleId) {
                foreach (DB::table('role_permissions')->where('role_id', $sourceRoleId)->pluck('permission_id') as $permissionId) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id' => $globalRoleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            DB::table('users')
                ->whereIn('custom_role_id', $sourceRoleIds)
                ->update(['custom_role_id' => $globalRoleId]);
        }

        DB::table('roles')
            ->whereIn('slug', ['tenant_admin', 'tenant_user'])
            ->where('is_system', true)
            ->whereNotNull('tenant_id')
            ->delete();
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->whereIn('role_id', DB::table('roles')->whereIn('slug', ['tenant_admin', 'tenant_user'])->whereNull('tenant_id')->pluck('id'))
            ->delete();

        DB::table('roles')
            ->whereIn('slug', ['tenant_admin', 'tenant_user'])
            ->whereNull('tenant_id')
            ->delete();
    }
};
