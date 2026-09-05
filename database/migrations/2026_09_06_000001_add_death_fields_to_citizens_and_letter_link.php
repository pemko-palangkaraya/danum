<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table): void {
            $table->date('tanggal_meninggal')->nullable()->after('status_kependudukan');
        });

        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->uuid('citizen_id')->nullable()->after('tenant_id');
            $table->foreign('citizen_id')->references('id')->on('citizens')->nullOnDelete();
            $table->index(['tenant_id', 'citizen_id']);
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropForeign(['citizen_id']);
            $table->dropIndex(['tenant_id', 'citizen_id']);
            $table->dropColumn('citizen_id');
        });

        Schema::table('citizens', function (Blueprint $table): void {
            $table->dropColumn('tanggal_meninggal');
        });
    }
};
