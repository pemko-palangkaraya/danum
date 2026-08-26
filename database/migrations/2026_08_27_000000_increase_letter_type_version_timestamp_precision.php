<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_type_versions', function (Blueprint $table): void {
            $table->timestampTz('effective_from', 6)->nullable()->change();
            $table->timestampTz('effective_until', 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('letter_type_versions', function (Blueprint $table): void {
            $table->timestampTz('effective_from')->nullable()->change();
            $table->timestampTz('effective_until')->nullable()->change();
        });
    }
};
