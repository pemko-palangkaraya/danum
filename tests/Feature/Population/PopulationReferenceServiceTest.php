<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Services\PopulationReferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PopulationReferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_labels_are_resolved_from_reference_data(): void
    {
        DB::table('population_reference_data')->insert([
            [
                'group' => 'religion',
                'code' => 'buddhist',
                'label' => 'Buddha',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'group' => 'gender',
                'code' => 'male',
                'label' => 'Laki-laki',
                'sort_order' => 1,
                'is_active' => true,
            ],
        ]);

        $service = app(PopulationReferenceService::class);

        $this->assertSame('Buddha', $service->label('religion', 'buddhist'));
        $this->assertSame('Laki-laki', $service->label('gender', 'male'));
        $this->assertSame('-', $service->label('religion', 'unknown'));
        $this->assertSame('-', $service->label('religion', null));
    }

    public function test_reference_labels_are_cached_within_the_service_instance(): void
    {
        DB::table('population_reference_data')->insert([
            'group' => 'religion',
            'code' => 'buddhist',
            'label' => 'Buddha',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $service = app(PopulationReferenceService::class);
        $service->labels('religion');

        DB::table('population_reference_data')
            ->where('group', 'religion')
            ->where('code', 'buddhist')
            ->update(['label' => 'Updated']);

        $this->assertSame('Buddha', $service->label('religion', 'buddhist'));
    }
}
