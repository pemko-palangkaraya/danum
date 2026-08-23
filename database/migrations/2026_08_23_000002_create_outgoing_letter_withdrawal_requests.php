<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('outgoing_letter_withdrawal_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('outgoing_letter_id')->constrained('outgoing_letters')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->timestampTz('requested_at');
            $table->text('reason');
            $table->string('statement_path');
            $table->string('status')->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampsTz();
            $table->index(['outgoing_letter_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letter_withdrawal_requests');
    }
};
