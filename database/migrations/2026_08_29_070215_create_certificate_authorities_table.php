<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_authorities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 20);
            $table->string('name', 255);
            $table->uuid('parent_id')->nullable();
            $table->string('serial_number', 128);
            $table->string('fingerprint_sha256', 64)->unique();
            $table->text('certificate_pem');
            $table->text('private_key_encrypted');
            $table->timestampTz('valid_from');
            $table->timestampTz('valid_until');
            $table->timestampTz('revoked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();

            $table->unique('id');
            $table->foreign('parent_id')
                ->references('id')
                ->on('certificate_authorities')
                ->nullOnDelete();
            $table->unique(['type', 'name']);
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_authorities');
    }
};
