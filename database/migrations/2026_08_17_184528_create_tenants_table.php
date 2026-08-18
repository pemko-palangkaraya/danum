<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('code', 50)->unique();
            $table->string('name', 150);

            $table->string('province', 100);
            $table->string('city', 100);
            $table->string('district', 100);
            $table->string('village', 100);

            $table->text('address')->nullable();

            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('logo')->nullable();

            $table->string('head_name', 150)->nullable();
            $table->string('head_title', 100)->nullable();

            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('province');
            $table->index('city');
            $table->index('district');
            $table->index('village');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};