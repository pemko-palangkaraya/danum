<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_members', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('family_id');
            $table->uuid('citizen_id');
            $table->string('hubungan_dalam_keluarga', 40);
            $table->unsignedInteger('urutan')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->foreign('family_id')->references('id')->on('families')->cascadeOnDelete();
            $table->foreign('citizen_id')->references('id')->on('citizens')->cascadeOnDelete();
            $table->unique(['family_id', 'citizen_id', 'tanggal_mulai']);
            $table->index(['citizen_id', 'status']);
            $table->index(['family_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
