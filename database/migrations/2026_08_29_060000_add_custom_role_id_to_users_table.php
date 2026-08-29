<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('custom_role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
            $table->index(['tenant_id', 'custom_role_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['custom_role_id']);
            $table->dropIndex(['tenant_id', 'custom_role_id']);
            $table->dropColumn('custom_role_id');
        });
    }
};
