<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('document_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type', 30)->default('public');
            $table->string('action', 40);
            $table->string('result', 40);
            $table->string('classification', 50)->nullable();
            $table->string('access_method', 30)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('outgoing_letters')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['document_id', 'created_at']);
            $table->index(['action', 'result']);
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_logs');
    }
};
