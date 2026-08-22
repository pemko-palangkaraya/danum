<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_type_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('letter_type_id')->constrained('letter_types')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('body_template');
            $table->timestamps();

            $table->unique(['letter_type_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_type_versions');
    }
};
