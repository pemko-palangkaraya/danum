<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_type_permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('letter_type_id')->constrained('letter_types')->cascadeOnDelete();
            $table->boolean('allowed')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'letter_type_id']);
            $table->index(['tenant_id', 'allowed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_type_permissions');
    }
};
