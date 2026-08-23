<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->timestampTz('valid_from')->nullable()->after('issued_at');
            $table->timestampTz('valid_until')->nullable()->after('valid_from');
            $table->index(['valid_from', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropIndex(['valid_from', 'valid_until']);
            $table->dropColumn(['valid_from', 'valid_until']);
        });
    }
};
