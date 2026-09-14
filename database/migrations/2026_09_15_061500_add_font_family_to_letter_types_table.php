<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('letter_types', 'font_family')) {
            Schema::table('letter_types', function (Blueprint $table): void {
                $table->string('font_family', 50)->default('Arial');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('letter_types', 'font_family')) {
            Schema::table('letter_types', function (Blueprint $table): void {
                $table->dropColumn('font_family');
            });
        }
    }
};
