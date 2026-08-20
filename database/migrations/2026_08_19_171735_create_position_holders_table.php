<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_holders', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->foreignUuid('position_id')
                ->constrained('positions')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();

            $table->index('position_id');
            $table->index('user_id');
            $table->index(['position_id', 'started_at']);
            $table->index(['position_id', 'ended_at']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX position_holders_one_active_per_position
             ON position_holders (position_id)
             WHERE ended_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS position_holders_one_active_per_position'
        );

        Schema::dropIfExists('position_holders');
    }
};
