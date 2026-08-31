<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('citizens')) {
            Schema::table('citizens', function (Blueprint $table): void {
                $table->unique(['tenant_id', 'nik'], 'citizens_tenant_nik_unique');
                $table->index(['tenant_id', 'nama_lengkap'], 'citizens_tenant_name_index');
                $table->index(['tenant_id', 'status_kependudukan'], 'citizens_tenant_status_index');
            });
        }

        if (Schema::hasTable('family_members')) {
            Schema::table('family_members', function (Blueprint $table): void {
                $table->index(['family_id', 'status'], 'family_members_family_status_index');
                $table->index(['citizen_id', 'status'], 'family_members_citizen_status_index');
            });

            DB::statement(<<<'SQL'
                CREATE UNIQUE INDEX family_members_one_active_citizen
                ON family_members (citizen_id)
                WHERE status = 'active'
            SQL);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('family_members')) {
            DB::statement('DROP INDEX IF EXISTS family_members_one_active_citizen');
            Schema::table('family_members', function (Blueprint $table): void {
                $table->dropIndex('family_members_family_status_index');
                $table->dropIndex('family_members_citizen_status_index');
            });
        }

        if (Schema::hasTable('citizens')) {
            Schema::table('citizens', function (Blueprint $table): void {
                $table->dropUnique('citizens_tenant_nik_unique');
                $table->dropIndex('citizens_tenant_name_index');
                $table->dropIndex('citizens_tenant_status_index');
            });
        }
    }
};
