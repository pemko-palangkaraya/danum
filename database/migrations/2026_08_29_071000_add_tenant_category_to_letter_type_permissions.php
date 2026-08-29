<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_type_permissions', function (Blueprint $table): void {
            $table->foreignId('tenant_category_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('tenant_categories')
                ->cascadeOnDelete();

            $table->dropForeign(['tenant_id']);
            $table->foreignUuid('tenant_id')->nullable()->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->dropUnique(['tenant_id', 'letter_type_id']);
            $table->unique(['tenant_category_id', 'letter_type_id']);
            $table->unique(['tenant_id', 'letter_type_id']);
            $table->index(['tenant_category_id', 'allowed']);
        });
    }

    public function down(): void
    {
        Schema::table('letter_type_permissions', function (Blueprint $table): void {
            $table->dropUnique(['tenant_category_id', 'letter_type_id']);
            $table->dropIndex(['tenant_category_id', 'allowed']);
            $table->dropForeign(['tenant_category_id']);
            $table->dropColumn('tenant_category_id');
        });
    }
};
