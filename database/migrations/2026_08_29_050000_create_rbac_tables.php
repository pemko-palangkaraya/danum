<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('scope')->default('tenant');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module');
            $table->string('action');
            $table->string('scope')->default('tenant');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        $now = now();
        $roles = [
            [UserRole::SUPER_ADMIN->value, 'Super Admin', 'global'],
            [UserRole::TENANT_ADMIN->value, 'Tenant Admin', 'tenant'],
            [UserRole::TENANT_USER->value, 'Tenant User', 'tenant'],
        ];

        foreach ($roles as [$slug, $name, $scope]) {
            DB::table('roles')->insert([
                'name' => $name,
                'slug' => $slug,
                'scope' => $scope,
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissions = [];
        foreach (PermissionEnum::cases() as $permission) {
            [$module, $action] = array_pad(explode('.', $permission->value, 2), 2, 'manage');
            $permissions[] = [
                'name' => str($permission->value)->replace(['.', '-'], ' ')->title()->toString(),
                'slug' => $permission->value,
                'module' => $module,
                'action' => $action,
                'scope' => $module === 'tenants' || $module === 'audit-logs' ? 'global' : 'tenant',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $permissions[] = [
            'name' => 'Manage RBAC',
            'slug' => 'rbac.manage',
            'module' => 'rbac',
            'action' => 'manage',
            'scope' => 'global',
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('permissions')->insert($permissions);

        $permissionIds = DB::table('permissions')->pluck('id', 'slug');
        $roleIds = DB::table('roles')->pluck('id', 'slug');

        foreach (PermissionEnum::forRole(UserRole::SUPER_ADMIN) as $permission) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleIds[UserRole::SUPER_ADMIN->value],
                'permission_id' => $permissionIds[$permission->value],
            ]);
        }
        DB::table('role_permissions')->insert([
            'role_id' => $roleIds[UserRole::SUPER_ADMIN->value],
            'permission_id' => $permissionIds['rbac.manage'],
        ]);

        foreach ([UserRole::TENANT_ADMIN, UserRole::TENANT_USER] as $role) {
            foreach (PermissionEnum::forRole($role) as $permission) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleIds[$role->value],
                    'permission_id' => $permissionIds[$permission->value],
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
