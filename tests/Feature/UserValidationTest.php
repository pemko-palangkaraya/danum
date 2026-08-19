<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_requires_a_valid_tenant(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this
            ->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'Tenant User',
                'email' => 'tenant-user@example.com',
                'password' => 'password',
                'role' => 'tenant_user',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_super_admin_cannot_be_assigned_to_a_tenant(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'Another Admin',
                'email' => 'another-admin@example.com',
                'password' => 'password',
                'role' => 'super_admin',
                'tenant_id' => $tenant->id,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_user_email_must_be_unique(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this
            ->actingAs($admin)
            ->putJson("/api/users/{$user->id}", [
                'email' => 'existing@example.com',
            ]);

        $response->assertOk();

        $response = $this
            ->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'Duplicate Email',
                'email' => 'existing@example.com',
                'password' => 'password',
                'role' => 'tenant_user',
                'tenant_id' => $tenant->id,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_tenant_user_cannot_remove_their_tenant_assignment(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $response = $this
            ->actingAs($admin)
            ->putJson("/api/users/{$user->id}", [
                'tenant_id' => null,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_super_admin_role_change_requires_removing_tenant_assignment(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $response = $this
            ->actingAs($admin)
            ->putJson("/api/users/{$user->id}", [
                'role' => 'super_admin',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }
}
