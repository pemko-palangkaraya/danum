<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_with_initial_user_creates_and_links_tenant_administrator(): void
    {
        $tenant = app(TenantService::class)->createWithInitialUser(
            ['code' => 'TST001', 'name' => 'Test Tenant'],
            ['name' => 'Initial Admin', 'email' => 'admin@test.local', 'password' => 'password'],
        );

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'code' => 'TST001',
            'administrator_user_id' => $tenant->administrator_user_id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $tenant->administrator_user_id,
            'email' => 'admin@test.local',
            'role' => UserRole::TENANT_USER->value,
            'status' => UserStatus::ACTIVE->value,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_update_can_update_tenant_and_existing_administrator(): void
    {
        $tenant = Tenant::factory()->create();
        $administrator = User::factory()->tenantAdmin($tenant)->create([
            'email' => 'administrator@test.local',
        ]);
        $tenant->forceFill(['administrator_user_id' => $administrator->id])->save();

        $updated = app(TenantService::class)->update($tenant->fresh(), [
            'name' => 'Updated Tenant',
            '_administrator' => [
                'name' => 'Updated Admin',
                'email' => 'administrator@test.local',
                'status' => UserStatus::ACTIVE->value,
            ],
        ]);

        $this->assertSame('Updated Tenant', $updated->name);
        $this->assertSame('Updated Admin', $updated->administrator->name);
        $this->assertSame('administrator@test.local', $updated->administrator->email);
    }

    public function test_update_preserves_administrator_when_no_administrator_payload_is_supplied(): void
    {
        $tenant = Tenant::factory()->create();
        $administrator = User::factory()->tenantAdmin($tenant)->create([
            'name' => 'Existing Admin',
            'email' => 'existing@test.local',
        ]);
        $tenant->forceFill(['administrator_user_id' => $administrator->id])->save();

        app(TenantService::class)->update($tenant->fresh(), [
            'name' => 'Tenant Without Admin Changes',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $administrator->id,
            'name' => 'Existing Admin',
            'email' => 'existing@test.local',
        ]);
    }

    public function test_update_changes_administrator_password_when_new_password_is_supplied(): void
    {
        $tenant = Tenant::factory()->create();
        $administrator = User::factory()->tenantAdmin($tenant)->create([
            'password' => 'old-password',
        ]);
        $tenant->forceFill(['administrator_user_id' => $administrator->id])->save();

        app(TenantService::class)->update($tenant->fresh(), [
            '_administrator' => [
                'name' => $administrator->name,
                'email' => $administrator->email,
                'password' => 'new-password',
                'status' => UserStatus::ACTIVE->value,
            ],
        ]);

        $administrator->refresh();

        $this->assertTrue(password_verify('new-password', $administrator->password));
        $this->assertFalse(password_verify('old-password', $administrator->password));
    }

    public function test_update_without_administrator_does_not_create_a_new_user(): void
    {
        $tenant = Tenant::factory()->create();
        $before = User::query()->count();

        app(TenantService::class)->update($tenant, [
            'name' => 'Updated Tenant',
        ]);

        $this->assertSame($before, User::query()->count());
        $this->assertNull($tenant->fresh()->administrator_user_id);
    }
}
