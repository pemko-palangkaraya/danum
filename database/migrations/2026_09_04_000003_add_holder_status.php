<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('position_holders', function (Blueprint $table): void {
            $table->string('assignment_status')->default('definitif')->after('user_id');
            $table->index(['position_id', 'assignment_status', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::table('position_holders', function (Blueprint $table): void {
            $table->dropIndex(['position_id', 'assignment_status', 'ended_at']);
            $table->dropColumn('assignment_status');
        });
    }
};
