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
        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('administrator_user_id')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('tenants')
            ->select('id')
            ->orderBy('id')
            ->each(function (object $tenant): void {
                $administratorId = DB::table('users')
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'tenant_user')
                    ->orderBy('id')
                    ->value('id');

                if ($administratorId !== null) {
                    DB::table('tenants')
                        ->where('id', $tenant->id)
                        ->update(['administrator_user_id' => $administratorId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropForeign(['administrator_user_id']);
            $table->dropColumn('administrator_user_id');
        });
    }
};
