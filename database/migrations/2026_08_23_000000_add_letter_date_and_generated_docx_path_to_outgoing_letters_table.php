<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->date('letter_date')->nullable()->after('issued_at');
            $table->string('generated_docx_path')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropColumn(['letter_date', 'generated_docx_path']);
        });
    }
};
