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
        Schema::create('tenant_position_structures', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('position_id')->constrained('positions')->restrictOnDelete();
            $table->foreignUuid('parent_position_id')->nullable()->constrained('positions')->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_root')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'position_id']);
            $table->index(['tenant_id', 'parent_position_id', 'sort_order']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX tenant_position_structures_one_root
             ON tenant_position_structures (tenant_id)
             WHERE is_root = true'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS tenant_position_structures_one_root');
        Schema::dropIfExists('tenant_position_structures');
    }
};
