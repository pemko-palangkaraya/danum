<?php

declare(strict_types=1);

use IlluminateDatabaseMigrationsMigration;
use IlluminateDatabaseSchemaBlueprint;
use IlluminateSupportFacadesSchema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_type_permissions', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->foreignUuid('tenant_id')->nullable()->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('letter_type_permissions', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->foreignUuid('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }
};
