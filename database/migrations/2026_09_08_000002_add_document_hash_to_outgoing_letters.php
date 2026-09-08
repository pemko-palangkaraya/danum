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
            $table->string('document_hash', 64)->nullable()->after('signed_at');
            $table->string('document_hash_algorithm', 20)->nullable()->after('document_hash');
            $table->index('document_hash');
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropIndex(['document_hash']);
            $table->dropColumn(['document_hash', 'document_hash_algorithm']);
        });
    }
};
