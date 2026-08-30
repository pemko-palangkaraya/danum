<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->uuid('tenant_category_id')->nullable()->after('tenant_id');
            $table->index(['tenant_category_id', 'status']);
        });

        DB::statement('UPDATE positions p SET tenant_category_id = t.tenant_category_id FROM tenants t WHERE t.id = p.tenant_id AND p.tenant_category_id IS NULL');

        Schema::table('positions', function (Blueprint $table): void {
            $table->foreign('tenant_category_id')->references('id')->on('tenant_categories')->restrictOnDelete();
        });

        // tenant_id remains temporarily as a legacy/source-tenant reference so existing
        // integrations and historical data remain readable. New application reads use
        // tenant_category_id to determine which tenants may use the master position.
        Schema::table('positions', function (Blueprint $table): void {
            $table->dropUnique('positions_tenant_id_code_unique');
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->unique(['tenant_category_id', 'code'], 'positions_category_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->dropUnique('positions_category_code_unique');
            $table->dropForeign(['tenant_category_id']);
            $table->dropIndex(['tenant_category_id', 'status']);
            $table->dropColumn('tenant_category_id');
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->unique(['tenant_id', 'code']);
        });
    }
};
