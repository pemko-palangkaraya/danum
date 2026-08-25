<?php

declare(strict_types=1);

namespace Tests\Feature\LetterTypes;

use App\Models\LetterType;
use App\Models\LetterTypeVersion;
use App\Services\LetterTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTypeTemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_with_template_creates_version_one(): void
    {
        $letterType = LetterType::factory()->create(['body_template' => 'Halo {{name}}']);
        app(LetterTypeService::class)->ensureCurrentVersion($letterType->refresh());
        $this->assertDatabaseHas('letter_type_versions', ['letter_type_id' => $letterType->id, 'version' => 1, 'body_template' => 'Halo {{name}}']);
    }

    public function test_same_template_does_not_create_duplicate_version(): void
    {
        $letterType = LetterType::factory()->create(['body_template' => 'Halo {{name}}']);
        $service = app(LetterTypeService::class);
        $first = $service->ensureCurrentVersion($letterType->refresh());
        $second = $service->ensureCurrentVersion($letterType->refresh());
        $this->assertSame($first?->id, $second?->id);
        $this->assertSame(1, LetterTypeVersion::query()->where('letter_type_id', $letterType->id)->count());
    }

    public function test_changed_template_creates_next_version_and_preserves_previous(): void
    {
        $letterType = LetterType::factory()->create(['body_template' => 'Versi {{name}}']);
        $service = app(LetterTypeService::class);
        $service->ensureCurrentVersion($letterType->refresh());
        $letterType->update(['body_template' => 'Versi baru {{name}}']);
        $service->ensureCurrentVersion($letterType->refresh());
        $this->assertDatabaseHas('letter_type_versions', ['letter_type_id' => $letterType->id, 'version' => 1, 'body_template' => 'Versi {{name}}']);
        $this->assertDatabaseHas('letter_type_versions', ['letter_type_id' => $letterType->id, 'version' => 2, 'body_template' => 'Versi baru {{name}}']);
    }

    public function test_letter_type_without_template_has_no_version(): void
    {
        $letterType = LetterType::factory()->create(['body_template' => null]);
        $this->assertNull(app(LetterTypeService::class)->ensureCurrentVersion($letterType));
        $this->assertDatabaseCount('letter_type_versions', 0);
    }
}
