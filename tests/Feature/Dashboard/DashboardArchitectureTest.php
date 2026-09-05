<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use Tests\TestCase;

class DashboardArchitectureTest extends TestCase
{
    public function test_dashboard_blade_is_free_of_business_logic_and_database_queries(): void
    {
        $view = file_get_contents(resource_path('views/livewire/pages/dashboard.blade.php'));

        $this->assertIsString($view);
        $this->assertStringNotContainsString('<?php', $view);
        $this->assertStringNotContainsString('@php', $view);
        $this->assertStringNotContainsString('::query(', $view);
        $this->assertStringNotContainsString('DB::', $view);
        $this->assertStringNotContainsString('auth()->user()->tenant', $view);
    }
}
