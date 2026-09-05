<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signer_certificates', function (Blueprint $table): void {
            $table->uuid('issuing_ca_id')->nullable()->after('user_id');
            $table->foreign('issuing_ca_id')->references('id')->on('certificate_authorities')->nullOnDelete();
            $table->index('issuing_ca_id');
        });
    }

    public function down(): void
    {
        Schema::table('signer_certificates', function (Blueprint $table): void {
            $table->dropForeign(['issuing_ca_id']);
            $table->dropIndex(['issuing_ca_id']);
            $table->dropColumn('issuing_ca_id');
        });
    }
};
