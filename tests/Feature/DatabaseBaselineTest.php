<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_testing_database_uses_postgresql(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
    }

    public function test_application_database_schema_can_be_migrated(): void
    {
        $this->assertNotEmpty(DB::select('select current_database()'));
    }
}
