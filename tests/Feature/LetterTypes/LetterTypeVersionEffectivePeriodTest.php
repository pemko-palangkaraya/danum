<?php

declare(strict_types=1);

namespace Tests\Feature\LetterTypes;

use App\Models\LetterType;
use App\Models\LetterTypeVersion;
use App\Services\LetterTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LetterTypeVersionEffectivePeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_version_is_resolved_by_effective_period(): void
    {
        $type = LetterType::factory()->create(['body_template' => 'v1']);
        $v1 = LetterTypeVersion::query()->create(['letter_type_id' => $type->id, 'version' => 1, 'body_template' => 'v1', 'is_active' => true, 'effective_from' => '2026-08-01 00:00:00', 'effective_until' => '2026-09-01 00:00:00']);
        $v2 = LetterTypeVersion::query()->create(['letter_type_id' => $type->id, 'version' => 2, 'body_template' => 'v2', 'is_active' => true, 'effective_from' => '2026-09-01 00:00:00']);

        $service = app(LetterTypeService::class);
        $this->assertSame($v1->id, $service->activeVersion($type, Carbon::parse('2026-08-25'))->id);
        $this->assertSame($v2->id, $service->activeVersion($type, Carbon::parse('2026-09-01'))->id);
    }

    public function test_inactive_version_is_never_resolved(): void
    {
        $type = LetterType::factory()->create(['body_template' => 'v1']);
        $old = LetterTypeVersion::query()->create(['letter_type_id' => $type->id, 'version' => 1, 'body_template' => 'old', 'is_active' => false, 'effective_from' => '2026-01-01 00:00:00']);
        LetterTypeVersion::query()->create(['letter_type_id' => $type->id, 'version' => 2, 'body_template' => 'new', 'is_active' => true, 'effective_from' => '2026-01-01 00:00:00']);

        $this->assertNotSame($old->id, app(LetterTypeService::class)->activeVersion($type, Carbon::parse('2026-08-25'))->id);
    }
}
