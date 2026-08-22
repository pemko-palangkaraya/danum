<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'letterhead_path')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('letterhead_path')->nullable()->after('logo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'letterhead_path')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->dropColumn('letterhead_path');
            });
        }
    }
};
