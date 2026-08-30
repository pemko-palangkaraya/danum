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
            $table->string('platform_role')->nullable()->after('role');
            $table->foreignId('custom_role_id')->nullable()->after('platform_role')->constrained('roles')->nullOnDelete();
            $table->index(['tenant_id', 'custom_role_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['custom_role_id']);
            $table->dropIndex(['tenant_id', 'custom_role_id']);
            $table->dropColumn(['platform_role', 'custom_role_id']);
        });
    }
};
