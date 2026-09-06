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
        Schema::table('letter_types', function (Blueprint $table): void {
            $table->foreignUuid('letter_classification_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('letter_classifications')
                ->restrictOnDelete();
        });

        $defaultId = DB::table('letter_classifications')
            ->where('code', '000')
            ->value('id');

        if ($defaultId === null) {
            $defaultId = (string) Str::uuid();
            DB::table('letter_classifications')->insert([
                'id' => $defaultId,
                'code' => '000',
                'name' => 'Umum',
                'description' => 'Klasifikasi umum untuk data jenis surat lama yang belum diklasifikasikan.',
                'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}/{{year}}',
                'number_padding' => 3,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('letter_types')
            ->whereNull('letter_classification_id')
            ->update(['letter_classification_id' => $defaultId]);

        Schema::table('letter_types', function (Blueprint $table): void {
            $table->foreignUuid('letter_classification_id')->nullable(false)->change();
            $table->index('letter_classification_id');
        });
    }

    public function down(): void
    {
        Schema::table('letter_types', function (Blueprint $table): void {
            $table->dropForeign(['letter_classification_id']);
            $table->dropIndex(['letter_classification_id']);
            $table->dropColumn('letter_classification_id');
        });
    }
};
