<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_letter_status_histories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('outgoing_letter_id')
                ->constrained('outgoing_letters')
                ->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->string('action', 20);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['outgoing_letter_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letter_status_histories');
    }
};
