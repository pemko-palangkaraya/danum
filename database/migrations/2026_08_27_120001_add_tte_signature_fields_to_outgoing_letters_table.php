<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = Schema::getColumnListing('outgoing_letters');

        Schema::table('outgoing_letters', function (Blueprint $table) use ($columns): void {
            if (! in_array('signed_pdf_path', $columns, true)) {
                $table->string('signed_pdf_path')->nullable()->after('generated_docx_path');
            }

            if (! in_array('signature_certificate_id', $columns, true)) {
                $table->uuid('signature_certificate_id')->nullable()->after('signed_pdf_path');
            }

            if (! in_array('signature_profile', $columns, true)) {
                $table->string('signature_profile')->nullable()->after('signature_certificate_id');
            }

            if (! in_array('signed_at', $columns, true)) {
                $table->timestampTz('signed_at')->nullable()->after('signature_profile');
            }
        });

        if (
            Schema::hasColumn('outgoing_letters', 'signature_certificate_id')
            && Schema::hasTable('signer_certificates')
            && ! DB::table('pg_constraint as c')
                ->join('pg_class as t', 't.oid', '=', 'c.conrelid')
                ->where('t.relname', 'outgoing_letters')
                ->where('c.contype', 'f')
                ->whereRaw("pg_get_constraintdef(c.oid) LIKE '%signature_certificate_id%signer_certificates%'")
                ->exists()
        ) {
            Schema::table('outgoing_letters', function (Blueprint $table): void {
                $table->foreign('signature_certificate_id')
                    ->references('id')
                    ->on('signer_certificates')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('outgoing_letters')) {
            return;
        }

        if (Schema::hasColumn('outgoing_letters', 'signature_certificate_id')) {
            Schema::table('outgoing_letters', function (Blueprint $table): void {
                $table->dropForeign(['signature_certificate_id']);
            });
        }

        $columns = [
            'signed_pdf_path',
            'signature_certificate_id',
            'signature_profile',
            'signed_at',
        ];

        $existingColumns = Schema::getColumnListing('outgoing_letters');
        $columns = array_values(array_intersect($columns, $existingColumns));

        if ($columns !== []) {
            Schema::table('outgoing_letters', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
