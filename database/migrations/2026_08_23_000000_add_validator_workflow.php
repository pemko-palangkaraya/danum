<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->boolean('can_validate')->default(false)->after('can_sign');
            $table->index(['tenant_id', 'status', 'can_validate']);
        });

        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->foreignUuid('validator_position_id')
                ->nullable()
                ->after('signer_title')
                ->constrained('positions')
                ->nullOnDelete();
            $table->foreignId('validator_user_id')
                ->nullable()
                ->after('validator_position_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('validator_name', 150)->nullable()->after('validator_user_id');
            $table->string('validator_title', 255)->nullable()->after('validator_name');

            $table->index(['tenant_id', 'validator_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropForeign(['validator_position_id']);
            $table->dropForeign(['validator_user_id']);
            $table->dropIndex(['tenant_id', 'validator_user_id']);
            $table->dropColumn([
                'validator_position_id',
                'validator_user_id',
                'validator_name',
                'validator_title',
            ]);
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'status', 'can_validate']);
            $table->dropColumn('can_validate');
        });
    }
};
