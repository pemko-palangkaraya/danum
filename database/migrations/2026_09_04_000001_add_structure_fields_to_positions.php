<?php

declare(strict_types=1);

use App\Enums\PositionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->string('position_type')->default(PositionType::MANAGERIAL->value)->after('name');
            $table->foreignUuid('parent_id')->nullable()->after('position_type')->constrained('positions')->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->after('parent_id');
            $table->index(['tenant_category_id', 'parent_id', 'sort_order']);
            $table->index(['tenant_category_id', 'position_type']);
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['tenant_category_id', 'parent_id', 'sort_order']);
            $table->dropIndex(['tenant_category_id', 'position_type']);
            $table->dropColumn(['position_type', 'parent_id', 'sort_order']);
        });
    }
};
