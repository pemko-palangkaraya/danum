<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_types', function (Blueprint $table): void {
            $table->string('template_path')->nullable()->after('body_template');
            $table->json('variables')->nullable()->after('template_path');
        });
    }

    public function down(): void
    {
        Schema::table('letter_types', function (Blueprint $table): void {
            $table->dropColumn(['template_path', 'variables']);
        });
    }
};
