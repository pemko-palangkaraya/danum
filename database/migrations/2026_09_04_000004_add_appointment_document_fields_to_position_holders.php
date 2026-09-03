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
            $table->string('appointment_number')->nullable()->after('assignment_status');
            $table->string('appointment_document_path')->nullable()->after('appointment_number');
            $table->index(['tenant_id', 'appointment_number']);
        });
    }

    public function down(): void
    {
        Schema::table('position_holders', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'appointment_number']);
            $table->dropColumn(['appointment_number', 'appointment_document_path']);
        });
    }
};
