<?php

declare(strict_types=1);

use App\Enums\VerificationAccessLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_classifications', function (Blueprint $table): void {
            $table->string('verification_access_level', 20)
                ->default(VerificationAccessLevel::PUBLIC->value)
                ->after('description');
            $table->index('verification_access_level');
        });
    }

    public function down(): void
    {
        Schema::table('letter_classifications', function (Blueprint $table): void {
            $table->dropIndex(['verification_access_level']);
            $table->dropColumn('verification_access_level');
        });
    }
};
