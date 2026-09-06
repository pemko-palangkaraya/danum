<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX citizens_tenant_nik_unique ON citizens (tenant_id, nik)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS citizens_tenant_nik_unique');
    }
};
