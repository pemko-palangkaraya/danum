<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_variable_definitions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key', 100)->unique();
            $table->string('label', 150);
            $table->string('type', 30)->default('text');
            $table->string('source', 30)->default('manual');
            $table->boolean('required')->default(false);
            $table->boolean('readonly')->default(false);
            $table->json('options')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_variable_definitions');
    }
};
