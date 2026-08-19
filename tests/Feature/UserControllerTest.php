<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('auth')->group(function (): void {
            Route::get('/test/users', [
                UserController::class,
                'index',
            ]);

            Route::post('/test/users', [
                UserController::class,
                'store',
            ]);

            Route::get('/test/users/{id}', [
                UserController::class,
                'show',
            ]);

            Route::put('/test/users/{id}', [
                UserController::class,
                'update',
            ]);

            Route::delete('/test/users/{id}', [
                UserController::class,
                'destroy',
            ]);
        });
    }

    public function test_super_admin_can_view_users(): void
    {
        User::factory()->count(2)->create();

        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $response = $this->actingAs($superAdmin)
            ->getJson('/test/users');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_tenant_user_cannot_view_user_list(): void
    {
        $tenantUser = User::factory()
            ->tenantUser()
            ->create();

        $response = $this->actingAs($tenantUser)
            ->getJson('/test/users');

        $response->assertForbidden();
    }

    public function test_super_admin_can_create_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/test/users', [
                'name' => 'Created User',
                'email' => 'created@example.com',
                'password' => 'password',
                'role' => 'tenant_user',
                'tenant_id' => $tenant->id,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Created User');

        $this->assertDatabaseHas('users', [
            'email' => 'created@example.com',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_tenant_user_cannot_create_user(): void
    {
        $tenantUser = User::factory()
            ->tenantUser()
            ->create();

        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($tenantUser)
            ->postJson('/test/users', [
                'name' => 'Created User',
                'email' => 'created-by-tenant@example.com',
                'password' => 'password',
                'role' => 'tenant_user',
                'tenant_id' => $tenant->id,
            ]);

        $response->assertForbidden();
    }

    public function test_super_admin_can_view_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $targetUser = User::factory()->create();

        $response = $this->actingAs($superAdmin)
            ->getJson("/test/users/{$targetUser->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $targetUser->id);
    }

    public function test_tenant_user_can_view_user_from_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $targetUser = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this->actingAs($user)
            ->getJson("/test/users/{$targetUser->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $targetUser->id);
    }

    public function test_tenant_user_cannot_view_user_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenantA)
            ->create();

        $targetUser = User::factory()
            ->tenantUser($tenantB)
            ->create();

        $response = $this->actingAs($user)
            ->getJson("/test/users/{$targetUser->id}");

        $response->assertForbidden();
    }

    public function test_show_returns_not_found_for_non_existing_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $response = $this->actingAs($superAdmin)
            ->getJson('/test/users/999999');

        $response
            ->assertNotFound()
            ->assertJson([
                'message' => 'User not found.',
            ]);
    }

    public function test_super_admin_can_update_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $targetUser = User::factory()->create([
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($superAdmin)
            ->putJson("/test/users/{$targetUser->id}", [
                'name' => 'Updated Name',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_tenant_user_cannot_update_user(): void
    {
        $tenantUser = User::factory()
            ->tenantUser()
            ->create();

        $targetUser = User::factory()->create();

        $response = $this->actingAs($tenantUser)
            ->putJson("/test/users/{$targetUser->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    }

    public function test_update_returns_not_found_for_non_existing_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $response = $this->actingAs($superAdmin)
            ->putJson('/test/users/999999', [
                'name' => 'Updated Name',
            ]);

        $response
            ->assertNotFound()
            ->assertJson([
                'message' => 'User not found.',
            ]);
    }

    public function test_super_admin_can_delete_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $targetUser = User::factory()->create();

        $response = $this->actingAs($superAdmin)
            ->deleteJson("/test/users/{$targetUser->id}");

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'User deleted successfully.',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_tenant_user_cannot_delete_user(): void
    {
        $tenantUser = User::factory()
            ->tenantUser()
            ->create();

        $targetUser = User::factory()->create();

        $response = $this->actingAs($tenantUser)
            ->deleteJson("/test/users/{$targetUser->id}");

        $response->assertForbidden();
    }

    public function test_delete_returns_not_found_for_non_existing_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $response = $this->actingAs($superAdmin)
            ->deleteJson('/test/users/999999');

        $response
            ->assertNotFound()
            ->assertJson([
                'message' => 'User not found.',
            ]);
    }
}
