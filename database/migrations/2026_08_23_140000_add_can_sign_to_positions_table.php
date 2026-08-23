<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->boolean('can_sign')->default(false)->after('status');
            $table->index(['tenant_id', 'can_sign', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'can_sign', 'status']);
            $table->dropColumn('can_sign');
        });
    }
};
