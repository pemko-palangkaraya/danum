<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signer_certificates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('position_id')->constrained('positions')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30)->default('self_signed');
            $table->string('serial_number', 128)->nullable();
            $table->string('fingerprint_sha256', 64)->unique();
            $table->text('certificate_pem');
            $table->text('private_key_encrypted');
            $table->timestampTz('valid_from');
            $table->timestampTz('valid_until');
            $table->timestampTz('revoked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['position_id', 'user_id', 'is_active']);
            $table->index(['valid_until', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signer_certificates');
    }
};
