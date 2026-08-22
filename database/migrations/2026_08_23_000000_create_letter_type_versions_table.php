<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_type_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('letter_type_id')->constrained('letter_types')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('body_template');
            $table->timestamps();

            $table->unique(['letter_type_id', 'version']);
        });

        $now = now();
        DB::table('letter_types')
            ->whereNotNull('body_template')
            ->orderBy('id')
            ->each(function (object $letterType) use ($now): void {
                DB::table('letter_type_versions')->insert([
                    'id' => (string) Str::uuid(),
                    'letter_type_id' => $letterType->id,
                    'version' => 1,
                    'body_template' => $letterType->body_template,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_type_versions');
    }
};
