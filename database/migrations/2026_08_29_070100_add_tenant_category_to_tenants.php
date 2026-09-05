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
        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('tenant_category_id')
                ->nullable()
                ->after('name')
                ->constrained('tenant_categories')
                ->restrictOnDelete();

            $table->foreignUuid('parent_tenant_id')
                ->nullable()
                ->after('tenant_category_id')
                ->constrained('tenants')
                ->nullOnDelete();

            $table->index('parent_tenant_id');
        });

        $lainnyaId = DB::table('tenant_categories')->where('code', 'lainnya')->value('id');

        if ($lainnyaId === null) {
            throw new \RuntimeException('Master tenant category "lainnya" tidak ditemukan.');
        }

        DB::table('tenants')
            ->whereNull('tenant_category_id')
            ->update(['tenant_category_id' => $lainnyaId]);

        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('tenant_category_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['parent_tenant_id']);
            $table->dropIndex(['parent_tenant_id']);
            $table->dropColumn('parent_tenant_id');
            $table->dropForeign(['tenant_category_id']);
            $table->dropColumn('tenant_category_id');
        });
    }
};
