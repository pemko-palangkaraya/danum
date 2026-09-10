<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('register_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('outgoing_letter_id')->nullable()->constrained('outgoing_letters')->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('register_number');
            $table->unsignedSmallInteger('register_year');
            $table->string('letter_number', 100);
            $table->date('letter_date');
            $table->string('letter_type_name', 255)->nullable();
            $table->string('classification_code', 100)->nullable();
            $table->string('subject', 255);
            $table->string('recipient_name', 255);
            $table->text('recipient_address')->nullable();
            $table->string('signer_name', 255)->nullable();
            $table->string('signer_title', 255)->nullable();
            $table->string('source', 20);
            $table->string('status', 20)->default('registered');
            $table->text('correction_reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'register_year', 'register_number'], 'register_entries_number_unique');
            $table->unique(['tenant_id', 'letter_number'], 'register_entries_letter_number_unique');
            $table->unique('outgoing_letter_id', 'register_entries_outgoing_letter_unique');
            $table->index(['tenant_id', 'register_year', 'source']);
            $table->index(['tenant_id', 'letter_date']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('register_entries');
    }
};
