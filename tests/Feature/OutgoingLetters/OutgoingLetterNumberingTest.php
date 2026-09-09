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

    public function test_number_is_global_per_tenant_across_classifications_and_year(): void
    {
        $category = TenantCategory::query()->updateOrCreate(
            ['code' => 'lainnya'],
            ['name' => 'Lainnya', 'sort_order' => 99, 'is_active' => true],
        );
        $tenant = Tenant::factory()->create(['tenant_category_id' => $category->id, 'code' => 'DINKES']);
        $firstClassification = LetterClassification::factory()->create([
            'code' => '400.10',
            'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}/{{month_roman}}/{{year}}',
            'number_padding' => 3,
        ]);
        $secondClassification = LetterClassification::factory()->create([
            'code' => '500.20',
            'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}/{{month_roman}}/{{year}}',
            'number_padding' => 3,
        ]);

        $service = app(OutgoingLetterNumberService::class);
        $date = Carbon::create(2026, 9, 7);

        self::assertSame('001/400.10/DINKES/IX/2026', $service->generate($tenant, $firstClassification, $date));
        self::assertSame('002/500.20/DINKES/IX/2026', $service->generate($tenant, $secondClassification, $date));
        self::assertSame('003/400.10/DINKES/IX/2026', $service->generate($tenant, $firstClassification, $date));
        self::assertSame('001/500.20/DINKES/I/2027', $service->generate($tenant, $secondClassification, Carbon::create(2027, 1, 1)));
    }

    public function test_manual_number_advances_global_sequence(): void
    {
        $category = TenantCategory::query()->updateOrCreate(
            ['code' => 'lainnya'],
            ['name' => 'Lainnya', 'sort_order' => 99, 'is_active' => true],
        );
        $tenant = Tenant::factory()->create(['tenant_category_id' => $category->id, 'code' => 'SETDA']);
        $firstClassification = LetterClassification::factory()->create([
            'code' => '100',
            'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}',
        ]);
        $secondClassification = LetterClassification::factory()->create([
            'code' => '400',
            'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}',
        ]);
        $service = app(OutgoingLetterNumberService::class);
        $date = Carbon::create(2026, 9, 7);

        self::assertSame('001/100/SETDA', $service->generate($tenant, $firstClassification, $date));
        self::assertSame('128/400/SETDA', $service->generate($tenant, $secondClassification, $date, 128));
        self::assertSame('129/100/SETDA', $service->generate($tenant, $firstClassification, $date));
    }

    public function test_manual_number_cannot_move_sequence_backwards(): void
    {
        $category = TenantCategory::query()->updateOrCreate(
            ['code' => 'lainnya'],
            ['name' => 'Lainnya', 'sort_order' => 99, 'is_active' => true],
        );
        $tenant = Tenant::factory()->create(['tenant_category_id' => $category->id, 'code' => 'SETDA']);
        $classification = LetterClassification::factory()->create([
            'code' => '100',
            'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}',
        ]);
        $service = app(OutgoingLetterNumberService::class);
        $date = Carbon::create(2026, 9, 7);

        $service->generate($tenant, $classification, $date, 128);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Nomor urut 127 sudah terlewati');
        $service->generate($tenant, $classification, $date, 127);
    }

    public function test_sequence_is_isolated_between_tenants(): void
    {
        $category = TenantCategory::query()->updateOrCreate(
            ['code' => 'lainnya'],
            ['name' => 'Lainnya', 'sort_order' => 99, 'is_active' => true],
        );
        $firstTenant = Tenant::factory()->create(['tenant_category_id' => $category->id, 'code' => 'SETDA']);
        $secondTenant = Tenant::factory()->create(['tenant_category_id' => $category->id, 'code' => 'DINKES']);
        $classification = LetterClassification::factory()->create([
            'code' => '100',
            'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}',
        ]);
        $service = app(OutgoingLetterNumberService::class);
        $date = Carbon::create(2026, 9, 7);

        self::assertSame('001/100/SETDA', $service->generate($firstTenant, $classification, $date));
        self::assertSame('001/100/DINKES', $service->generate($secondTenant, $classification, $date));
    }
}
