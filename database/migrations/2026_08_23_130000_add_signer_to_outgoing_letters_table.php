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
            $table->foreignUuid('signer_position_id')->nullable()->after('letter_type_version_id')->nullOnDelete();
            $table->foreignId('signer_user_id')->nullable()->after('signer_position_id')->nullOnDelete();
            $table->string('signer_name', 150)->nullable()->after('signer_user_id');
            $table->string('signer_title', 150)->nullable()->after('signer_name');

            $table->index(['tenant_id', 'signer_position_id']);
            $table->index(['tenant_id', 'signer_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'signer_position_id']);
            $table->dropIndex(['tenant_id', 'signer_user_id']);
            $table->dropForeign(['signer_position_id']);
            $table->dropForeign(['signer_user_id']);
            $table->dropColumn(['signer_position_id', 'signer_user_id', 'signer_name', 'signer_title']);
        });
    }
};
