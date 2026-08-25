<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_type_versions', function (Blueprint $table): void {
            $table->json('variables')->nullable()->after('template_path');
        });

        // Preserve the variable definition that existed when each historical
        // version was created. Older rows are backfilled from the current
        // LetterType definition; newly-created versions always write their own snapshot.
        foreach (\DB::table('letter_type_versions')->orderBy('id')->get() as $version) {
            $variables = \DB::table('letter_types')->where('id', $version->letter_type_id)->value('variables');
            \DB::table('letter_type_versions')->where('id', $version->id)->update([
                'variables' => $variables ?: json_encode([]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('letter_type_versions', function (Blueprint $table): void {
            $table->dropColumn('variables');
        });
    }
};
