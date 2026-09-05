<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_letters', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('letter_type_id')->constrained('letter_types')->restrictOnDelete();
            $table->string('number', 100);
            $table->string('recipient_name', 150);
            $table->text('recipient_address')->nullable();
            $table->string('subject', 255);
            $table->text('content');
            $table->date('issued_at')->nullable();
            $table->string('status', 20);
            $table->string('unsigned_pdf_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status']);
            $table->index('letter_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letters');
    }
};
