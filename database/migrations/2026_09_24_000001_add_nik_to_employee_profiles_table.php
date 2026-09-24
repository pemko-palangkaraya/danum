<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table): void {
            $table->string('nik', 16)->nullable()->unique()->after('nip');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table): void {
            $table->dropUnique(['nik']);
            $table->dropColumn('nik');
        });
    }
};
