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
            $table->unsignedTinyInteger('letterhead_line1_size')->default(15)->after('letterhead_line3');
            $table->unsignedTinyInteger('letterhead_line2_size')->default(13)->after('letterhead_line1_size');
            $table->unsignedTinyInteger('letterhead_line3_size')->default(11)->after('letterhead_line2_size');
            $table->unsignedTinyInteger('letterhead_meta_size')->default(8)->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'letterhead_line1_size',
                'letterhead_line2_size',
                'letterhead_line3_size',
                'letterhead_meta_size',
            ]);
        });
    }
};
