<?php

declare(strict_types=1);

namespace Tests\Feature\LetterTypes;

use App\Models\LetterType;
use App\Services\LetterTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTypeVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_creates_initial_version(): void
    {
        $letterType = LetterType::factory()->create([
            'body_template' => 'Nomor: {{ number }}',
        ]);

        $version = app(LetterTypeService::class)->ensureCurrentVersion($letterType);

        $this->assertNotNull($version);
        $this->assertSame(1, $version->version);
        $this->assertSame('Nomor: {{ number }}', $version->body_template);
    }

    public function test_same_template_does_not_create_duplicate_version(): void
    {
        $letterType = LetterType::factory()->create([
            'body_template' => 'Nomor: {{ number }}',
        ]);
        $service = app(LetterTypeService::class);

        $first = $service->ensureCurrentVersion($letterType);
        $second = $service->ensureCurrentVersion($letterType->refresh());

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('letter_type_versions', 1);
    }

    public function test_changed_template_creates_next_version_and_preserves_previous(): void
    {
        $letterType = LetterType::factory()->create([
            'body_template' => 'Versi {{ one }}',
        ]);
        $service = app(LetterTypeService::class);
        $first = $service->ensureCurrentVersion($letterType);

        $letterType->update(['body_template' => 'Versi {{ two }}']);
        $second = $service->ensureCurrentVersion($letterType->refresh());

        $this->assertSame(2, $second->version);
        $this->assertNotSame($first->id, $second->id);
        $this->assertDatabaseHas('letter_type_versions', [
            'id' => $first->id,
            'version' => 1,
            'body_template' => 'Versi {{ one }}',
        ]);
        $this->assertDatabaseHas('letter_type_versions', [
            'id' => $second->id,
            'version' => 2,
            'body_template' => 'Versi {{ two }}',
        ]);
    }

    public function test_letter_type_without_template_has_no_version(): void
    {
        $letterType = LetterType::factory()->create(['body_template' => null]);

        $this->assertNull(app(LetterTypeService::class)->ensureCurrentVersion($letterType));
        $this->assertDatabaseCount('letter_type_versions', 0);
    }
}
