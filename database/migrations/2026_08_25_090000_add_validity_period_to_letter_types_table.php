<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_types', function (Blueprint $table): void {
            $table->string('validity_period', 20)->default('none')->after('has_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('letter_types', function (Blueprint $table): void {
            $table->dropColumn('validity_period');
        });
    }
};
