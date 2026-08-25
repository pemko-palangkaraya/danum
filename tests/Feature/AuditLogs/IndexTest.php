<?php

declare(strict_types=1);

namespace Tests\Feature\AuditLogs;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_audit_log_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();

        AuditLog::query()->create([
            'user_id' => $superAdmin->id,
            'tenant_id' => $tenant->id,
            'action' => 'tenant.updated',
            'auditable_type' => Tenant::class,
            'auditable_id' => $tenant->id,
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => $tenant->name],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Audit Log')
            ->assertSee('tenant.updated')
            ->assertSee($superAdmin->name)
            ->assertSee($tenant->name);
    }

    public function test_tenant_user_cannot_open_audit_log_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $this->actingAs($user)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }
}
