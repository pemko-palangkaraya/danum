<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table): void {
            $table->string('rt', 10)->nullable()->change();
            $table->string('rw', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table): void {
            $table->string('rt', 3)->nullable()->change();
            $table->string('rw', 3)->nullable()->change();
        });
    }
};
