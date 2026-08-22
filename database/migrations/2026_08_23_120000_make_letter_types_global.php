<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_types', function (Blueprint $table): void {
            $table->foreignUuid('tenant_id')->nullable()->change();
        });

        DB::statement(
            'CREATE UNIQUE INDEX letter_types_global_code_unique ON letter_types (code) WHERE tenant_id IS NULL AND deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS letter_types_global_code_unique');

        Schema::table('letter_types', function (Blueprint $table): void {
            $table->foreignUuid('tenant_id')->nullable(false)->change();
        });
    }
};
