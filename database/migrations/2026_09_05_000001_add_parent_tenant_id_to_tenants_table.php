<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignUuid('parent_tenant_id')
                ->nullable()
                ->after('tenant_category_id')
                ->constrained('tenants')
                ->nullOnDelete();

            $table->index('parent_tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['parent_tenant_id']);
            $table->dropIndex(['parent_tenant_id']);
            $table->dropColumn('parent_tenant_id');
        });
    }
};
