<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\TenantAdministratorSeeder;
use Database\Seeders\TenantReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAdministratorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_reference_tenants_receive_a_tenant_administrator(): void
    {
        $this->seed(TenantReferenceSeeder::class);
        $this->seed(TenantAdministratorSeeder::class);

        $tenants = Tenant::query()->get();
        $role = Role::query()
            ->where('slug', 'tenant_admin')
            ->whereNull('tenant_id')
            ->where('is_system', true)
            ->firstOrFail();

        $this->assertNotEmpty($tenants);
        $this->assertSame(
            $tenants->count(),
            User::query()->where('custom_role_id', $role->id)->count(),
        );

        foreach ($tenants as $tenant) {
            $administrator = $tenant->administrator;

            $this->assertNotNull($administrator);
            $this->assertSame($tenant->id, $administrator->tenant_id);
            $this->assertSame('tenant_admin', $administrator->roleModel()?->slug);
            $this->assertSame($tenant->code.'@danum.local', $administrator->email);
        }
    }
}
