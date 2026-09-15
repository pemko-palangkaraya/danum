<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_letter_attachments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('outgoing_letter_id')->constrained('outgoing_letters')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('title', 255);
            $table->string('source', 20)->default('uploaded');
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->default('application/pdf');
            $table->unsignedInteger('page_count');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->unique(['outgoing_letter_id', 'sequence']);
            $table->index(['outgoing_letter_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letter_attachments');
    }
};
