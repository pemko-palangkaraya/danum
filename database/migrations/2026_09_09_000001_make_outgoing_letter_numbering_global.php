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
        DB::transaction(function (): void {
            $sequences = DB::table('letter_number_sequences')
                ->select('id', 'tenant_id', 'year', 'last_number')
                ->orderBy('tenant_id')
                ->orderBy('year')
                ->get()
                ->groupBy(fn (object $row): string => $row->tenant_id . '|' . $row->year);

            foreach ($sequences as $rows) {
                $first = $rows->first();
                if ($first === null) {
                    continue;
                }

                $maximum = (int) $rows->max('last_number');

                DB::table('letter_number_sequences')
                    ->where('id', $first->id)
                    ->update(['last_number' => $maximum, 'updated_at' => now()]);

                $duplicateIds = $rows->skip(1)->pluck('id')->all();
                if ($duplicateIds !== []) {
                    DB::table('letter_number_sequences')->whereIn('id', $duplicateIds)->delete();
                }
            }

            Schema::table('letter_number_sequences', function (Blueprint $table): void {
                $table->dropUnique('letter_number_sequences_scope_unique');
                $table->dropForeign(['letter_classification_id']);
                $table->dropColumn('letter_classification_id');
                $table->unique(['tenant_id', 'year'], 'letter_number_sequences_scope_unique');
            });
        });

        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->unsignedInteger('sequence_number')->nullable()->after('number');
            $table->unsignedSmallInteger('sequence_year')->nullable()->after('sequence_number');
            $table->unique(
                ['tenant_id', 'sequence_year', 'sequence_number'],
                'outgoing_letters_sequence_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropUnique('outgoing_letters_sequence_unique');
            $table->dropColumn(['sequence_number', 'sequence_year']);
        });

        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->dropUnique('letter_number_sequences_scope_unique');
            $table->foreignUuid('letter_classification_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('letter_classifications')
                ->restrictOnDelete();
            $table->unique(
                ['tenant_id', 'letter_classification_id', 'year'],
                'letter_number_sequences_scope_unique',
            );
        });
    }
};
