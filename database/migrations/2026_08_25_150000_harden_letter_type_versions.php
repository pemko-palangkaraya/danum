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
            $table->string('template_path')->nullable()->after('body_template');
            $table->timestampTz('effective_from')->nullable()->after('template_path');
            $table->timestampTz('effective_until')->nullable()->after('effective_from');
            $table->boolean('is_active')->default(true)->after('effective_until');
            $table->text('change_note')->nullable()->after('is_active');
            $table->unsignedBigInteger('created_by')->nullable()->after('change_note');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['letter_type_id', 'is_active']);
            $table->index(['effective_from', 'effective_until']);
        });
    }

    public function down(): void
    {
        Schema::table('letter_type_versions', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['letter_type_id', 'is_active']);
            $table->dropIndex(['effective_from', 'effective_until']);
            $table->dropColumn([
                'template_path', 'effective_from', 'effective_until',
                'is_active', 'change_note', 'created_by',
            ]);
        });
    }
};
