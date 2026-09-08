<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('population_reference_data')
            ->where('group', 'family_relationship')
            ->where('code', 'spouse')
            ->update(['label' => 'Istri', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('population_reference_data')
            ->where('group', 'family_relationship')
            ->where('code', 'spouse')
            ->update(['label' => 'Istri/Suami', 'updated_at' => now()]);
    }
};
