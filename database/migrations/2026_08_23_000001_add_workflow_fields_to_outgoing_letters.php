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
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('tenant_id')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->json('input_data')->nullable()->after('content');
            $table->text('rejection_reason')->nullable()->after('verification_token');
            $table->foreignId('rejected_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
        });

        DB::statement("UPDATE outgoing_letters ol SET created_by = h.changed_by FROM (SELECT DISTINCT ON (outgoing_letter_id) outgoing_letter_id, changed_by FROM outgoing_letter_status_histories WHERE action = 'created' ORDER BY outgoing_letter_id, created_at ASC) h WHERE ol.id = h.outgoing_letter_id AND ol.created_by IS NULL");
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['submitted_at', 'input_data', 'rejection_reason', 'rejected_at']);
        });
    }
};
