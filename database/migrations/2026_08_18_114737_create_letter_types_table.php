<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignUuid('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignUuid('letter_classification_id')
                ->constrained('letter_classifications')
                ->restrictOnDelete();

            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->text('body_template')->nullable();
            $table->string('template_path')->nullable();
            $table->json('variables')->nullable();

            $table->string('status', 20);
            $table->string('font_family', 50)->default('Arial');
            $table->boolean('has_expiry')->default(false);
            $table->string('validity_period', 20)->default('none');
            $table->unsignedInteger('validity_days')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->timestampTz('deletion_scheduled_at')->nullable();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['code'], 'letter_types_global_code_unique');
            $table->index('letter_classification_id');
            $table->index('deletion_scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_types');
    }
};
