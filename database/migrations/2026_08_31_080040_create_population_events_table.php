<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('population_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('citizen_id')->nullable();
            $table->uuid('family_id')->nullable();
            $table->string('event_type', 40);
            $table->date('event_date');
            $table->date('effective_date')->nullable();
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->string('document_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('citizen_id')->references('id')->on('citizens')->nullOnDelete();
            $table->foreign('family_id')->references('id')->on('families')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'event_type', 'event_date']);
            $table->index(['citizen_id', 'event_date']);
            $table->index(['family_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('population_events');
    }
};
