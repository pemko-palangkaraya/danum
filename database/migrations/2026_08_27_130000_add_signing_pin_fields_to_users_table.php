<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('signing_pin_hash', 255)->nullable()->after('password');
            $table->timestampTz('signing_pin_set_at')->nullable()->after('signing_pin_hash');
            $table->unsignedSmallInteger('signing_pin_failed_attempts')->default(0)->after('signing_pin_set_at');
            $table->timestampTz('signing_pin_locked_until')->nullable()->after('signing_pin_failed_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'signing_pin_hash',
                'signing_pin_set_at',
                'signing_pin_failed_attempts',
                'signing_pin_locked_until',
            ]);
        });
    }
};
