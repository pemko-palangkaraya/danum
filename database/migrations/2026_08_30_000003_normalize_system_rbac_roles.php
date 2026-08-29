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

        // Canonical system roles are global records with tenant_id NULL, but
        // tenant_admin and tenant_user remain tenant-scoped roles by contract.
        foreach ([
            'tenant_admin' => 'Tenant Admin',
            'tenant_user' => 'Tenant User',
        ] as $slug => $name) {
            DB::table('roles')
                ->whereNull('tenant_id')
                ->where('slug', $slug)
                ->update([
                    'name' => $name,
                    'scope' => 'tenant',
                    'is_system' => true,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            if (! DB::table('roles')->whereNull('tenant_id')->where('slug', $slug)->exists()) {
                DB::table('roles')->insert([
                    'tenant_id' => null,
                    'name' => $name,
                    'slug' => $slug,
                    'scope' => 'tenant',
                    'is_system' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Existing tenant-scoped system role copies are unnecessary once users
        // have been reassigned to the canonical system role records.
        foreach (['tenant_admin', 'tenant_user'] as $slug) {
            $globalRoleId = DB::table('roles')
                ->whereNull('tenant_id')
                ->where('slug', $slug)
                ->value('id');

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

            if ($sourceRoleIds->isNotEmpty()) {
                DB::table('users')
                    ->whereIn('custom_role_id', $sourceRoleIds)
                    ->update(['custom_role_id' => $globalRoleId]);

                DB::table('roles')
                    ->whereIn('id', $sourceRoleIds)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->whereIn(
                'role_id',
                DB::table('roles')
                    ->whereIn('slug', ['tenant_admin', 'tenant_user'])
                    ->whereNull('tenant_id')
                    ->pluck('id')
            )
            ->delete();

        DB::table('roles')
            ->whereIn('slug', ['tenant_admin', 'tenant_user'])
            ->whereNull('tenant_id')
            ->delete();
    }
};
