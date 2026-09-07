<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_classifications', function (Blueprint $table): void {
            $table->dropUnique('letter_classifications_code_unique');
            $table->unsignedInteger('source_order')->nullable()->unique();
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::table('letter_classifications', function (Blueprint $table): void {
            $table->dropUnique('letter_classifications_source_order_unique');
            $table->dropIndex('letter_classifications_code_index');
            $table->dropColumn('source_order');
            $table->unique('code');
        });
    }
};
