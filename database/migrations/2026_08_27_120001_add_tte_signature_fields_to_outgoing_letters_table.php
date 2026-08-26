<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->string('signed_pdf_path')->nullable()->after('generated_docx_path');
            $table->uuid('signature_certificate_id')->nullable()->after('signed_pdf_path');
            $table->string('signature_profile')->nullable()->after('signature_certificate_id');
            $table->timestampTz('signed_at')->nullable()->after('signature_profile');

            $table->foreign('signature_certificate_id')
                ->references('id')
                ->on('signer_certificates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropForeign(['signature_certificate_id']);
            $table->dropColumn([
                'signed_pdf_path',
                'signature_certificate_id',
                'signature_profile',
                'signed_at',
            ]);
        });
    }
};
