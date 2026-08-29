<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('slug', PermissionEnum::RBAC_MANAGE->value)
            ->update([
                'scope' => 'global',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('slug', PermissionEnum::RBAC_MANAGE->value)
            ->update([
                'scope' => 'tenant',
                'updated_at' => now(),
            ]);
    }
};
