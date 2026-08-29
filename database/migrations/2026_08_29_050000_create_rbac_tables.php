<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('scope')->default('tenant');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module');
            $table->string('action');
            $table->string('scope')->default('tenant');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        $now = now();
        $rows = [];
        foreach (PermissionEnum::cases() as $permission) {
            [$module, $action] = array_pad(explode('.', $permission->value, 2), 2, 'manage');
            $rows[] = [
                'name' => str($permission->value)->replace(['.', '-'], ' ')->title()->toString(),
                'slug' => $permission->value,
                'module' => $module,
                'action' => $action,
                'scope' => in_array($module, ['tenants', 'audit-logs'], true) ? 'global' : 'tenant',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('permissions')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
