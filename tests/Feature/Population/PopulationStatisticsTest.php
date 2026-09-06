<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Models\Citizen;
use App\Models\Tenant;
use App\Services\PopulationStatisticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopulationStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-06');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_statistics_count_only_living_population_and_current_year_births_and_deaths(): void
    {
        $tenant = Tenant::factory()->create();

        Citizen::factory()->forTenant($tenant)->create([
            'tanggal_lahir' => '1990-01-01',
            'status_kependudukan' => 'active',
        ]);
        Citizen::factory()->forTenant($tenant)->create([
            'tanggal_lahir' => '2026-04-15',
            'status_kependudukan' => 'active',
        ]);
        Citizen::factory()->forTenant($tenant)->create([
            'tanggal_lahir' => '1985-02-01',
            'tanggal_meninggal' => '2026-03-10',
            'status_kependudukan' => 'meninggal',
        ]);
        Citizen::factory()->forTenant($tenant)->create([
            'tanggal_lahir' => '1975-02-01',
            'tanggal_meninggal' => '2025-12-10',
            'status_kependudukan' => 'meninggal',
        ]);

        $statistics = app(PopulationStatisticsService::class)->summarize($tenant->id);

        $this->assertSame(2, $statistics['totalCitizens']);
        $this->assertSame(2, $statistics['activeCitizens']);
        $this->assertSame(2, $statistics['deceasedCitizens']);
        $this->assertSame(1, $statistics['deceasedThisYear']);
        $this->assertSame(1, $statistics['birthsThisYear']);
        $this->assertSame(2026, $statistics['currentYear']);
    }
}
