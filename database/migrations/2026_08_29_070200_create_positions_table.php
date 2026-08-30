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
        Schema::create('positions', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignId('tenant_category_id')
                ->constrained('tenant_categories')
                ->restrictOnDelete();

            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status');
            $table->boolean('can_sign')->default(false);
            $table->boolean('can_validate')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_category_id', 'status']);
            $table->index(['tenant_category_id', 'status', 'can_validate']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX positions_category_code_unique
             ON positions (tenant_category_id, code)
             WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS positions_category_code_unique');
        Schema::dropIfExists('positions');
    }
};
