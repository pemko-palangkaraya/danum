<?php

declare(strict_types=1);

namespace Tests\Feature\TenantProfile;

use Tests\TestCase;

class TenantProfileArchitectureTest extends TestCase
{
    public function test_tenant_profile_blade_is_free_of_php_and_database_logic(): void
    {
        $view = file_get_contents(resource_path('views/livewire/pages/tenant-profile.blade.php'));

        $this->assertIsString($view);
        $this->assertStringNotContainsString('<?php', $view);
        $this->assertStringNotContainsString('@php', $view);
        $this->assertStringNotContainsString('::query(', $view);
        $this->assertStringNotContainsString('DB::', $view);
        $this->assertStringNotContainsString('auth()->user()', $view);
    }
}
