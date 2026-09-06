<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Models\LetterClassification;
use App\Models\Tenant;
use App\Models\TenantCategory;
use App\Services\OutgoingLetterNumberService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_number_is_sequential_per_tenant_classification_and_year(): void
    {
        $category = TenantCategory::query()->updateOrCreate(
            ['code' => 'lainnya'],
            ['name' => 'Lainnya', 'sort_order' => 99, 'is_active' => true],
        );
        $tenant = Tenant::factory()->create(['tenant_category_id' => $category->id, 'code' => 'DINKES']);
        $classification = LetterClassification::factory()->create([
            'code' => '400.10',
            'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}/{{year}}',
            'number_padding' => 3,
        ]);

        $service = app(OutgoingLetterNumberService::class);
        $date = Carbon::create(2026, 9, 7);

        $first = $service->generate($tenant, $classification, $date);
        $second = $service->generate($tenant, $classification, $date);

        self::assertSame('001/400.10/DINKES/2026', $first);
        self::assertSame('002/400.10/DINKES/2026', $second);
    }

    public function test_number_sequence_starts_again_for_new_classification_or_year(): void
    {
        $category = TenantCategory::query()->updateOrCreate(
            ['code' => 'lainnya'],
            ['name' => 'Lainnya', 'sort_order' => 99, 'is_active' => true],
        );
        $tenant = Tenant::factory()->create(['tenant_category_id' => $category->id, 'code' => 'SETDA']);
        $firstClassification = LetterClassification::factory()->create(['code' => '100', 'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}']);
        $secondClassification = LetterClassification::factory()->create(['code' => '400', 'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}']);
        $service = app(OutgoingLetterNumberService::class);

        self::assertSame('001/100/SETDA', $service->generate($tenant, $firstClassification, Carbon::create(2026, 12, 31)));
        self::assertSame('001/400/SETDA', $service->generate($tenant, $secondClassification, Carbon::create(2026, 12, 31)));
        self::assertSame('001/100/SETDA', $service->generate($tenant, $firstClassification, Carbon::create(2027, 1, 1)));
    }
}
