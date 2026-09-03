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
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('slug', ['population.view', 'population.manage'])
            ->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
