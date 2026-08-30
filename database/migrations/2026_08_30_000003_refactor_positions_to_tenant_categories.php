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
        Schema::table('positions', function (Blueprint $table): void {
            $table->foreignId('tenant_category_id')->nullable()->after('tenant_id');
            $table->index(['tenant_category_id', 'status']);
        });

        DB::statement('UPDATE positions p SET tenant_category_id = t.tenant_category_id FROM tenants t WHERE t.id = p.tenant_id AND p.tenant_category_id IS NULL');

        Schema::table('positions', function (Blueprint $table): void {
            $table->foreign('tenant_category_id')->references('id')->on('tenant_categories')->restrictOnDelete();
            $table->dropUnique('positions_tenant_id_code_unique');
            $table->dropIndex('positions_tenant_id_status_index');
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('position_holders', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable()->after('position_id');
            $table->index(['tenant_id', 'position_id']);
        });

        DB::statement('UPDATE position_holders ph SET tenant_id = u.tenant_id FROM users u WHERE u.id = ph.user_id AND ph.tenant_id IS NULL');

        Schema::table('position_holders', function (Blueprint $table): void {
            $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
        });

        DB::statement('DROP INDEX IF EXISTS position_holders_one_active_per_position');
        DB::statement('CREATE UNIQUE INDEX position_holders_one_active_per_tenant_position ON position_holders (tenant_id, position_id) WHERE ended_at IS NULL');
    }

    public function down(): void
    {
        throw new \LogicException('This development migration is intentionally irreversible. Use migrate:fresh.');
    }
};
