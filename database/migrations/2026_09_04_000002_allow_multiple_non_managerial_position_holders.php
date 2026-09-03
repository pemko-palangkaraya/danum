<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS position_holders_one_active_per_tenant_position');
        DB::statement(
            'CREATE UNIQUE INDEX position_holders_same_user_one_active_per_position
             ON position_holders (tenant_id, position_id, user_id)
             WHERE ended_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS position_holders_same_user_one_active_per_position');
        DB::statement(
            'CREATE UNIQUE INDEX position_holders_one_active_per_tenant_position
             ON position_holders (tenant_id, position_id)
             WHERE ended_at IS NULL'
        );
    }
};
