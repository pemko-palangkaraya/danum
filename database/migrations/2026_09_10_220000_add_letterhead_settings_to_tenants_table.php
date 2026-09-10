<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('letterhead_line1', 150)->nullable()->after('letterhead_path');
            $table->string('letterhead_line2', 150)->nullable()->after('letterhead_line1');
            $table->string('letterhead_line3', 150)->nullable()->after('letterhead_line2');
            $table->string('postal_code', 10)->nullable()->after('address');
            $table->string('website', 255)->nullable()->after('email');
            $table->string('head_nip', 30)->nullable()->after('head_title');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'letterhead_line1',
                'letterhead_line2',
                'letterhead_line3',
                'postal_code',
                'website',
                'head_nip',
            ]);
        });
    }
};
