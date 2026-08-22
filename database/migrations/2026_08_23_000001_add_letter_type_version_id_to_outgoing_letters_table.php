<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->foreignUuid('letter_type_version_id')
                ->nullable()
                ->after('letter_type_id')
                ->constrained('letter_type_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropForeign(['letter_type_version_id']);
            $table->dropColumn('letter_type_version_id');
        });
    }
};
