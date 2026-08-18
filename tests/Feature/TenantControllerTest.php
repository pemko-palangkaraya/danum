<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tenants(): void
    {
        Tenant::factory()->count(3)->create();

        $user = User::factory()
            ->superAdmin()
            ->create();

        // $response = $this->getJson('/api/tenants');
        $response = $this
            ->actingAs($user)
            ->getJson('/api/tenants');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_tenant(): void
    {
        $data = [
            'code' => 'TNT001',
            'name' => 'Tenant Test',
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Pahandut',
            'village' => 'Mungku Baru',
            'address' => 'Alamat Test',
            'phone' => '08123456789',
            'email' => 'tenant@example.com',
            'logo' => null,
            'head_name' => 'Test Head',
            'head_title' => 'Kepala Kelurahan',
            'status' => TenantStatus::ACTIVE->value,
        ];

        $user = User::factory()
            ->superAdmin()
            ->create();

        // $response = $this->getJson('/api/tenants');
        $response = $this
            ->actingAs($user)
            ->postJson('/api/tenants', $data);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'TNT001')
            ->assertJsonPath('data.name', 'Tenant Test');

        $this->assertDatabaseHas('tenants', [
            'code' => 'TNT001',
            'name' => 'Tenant Test',
        ]);
    }

    public function test_can_show_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        // $response = $this->getJson(
        //     "/api/tenants/{$tenant->id}",
        // );

        $user = User::factory()
            ->superAdmin()
            ->create();

        $response = $this
            ->actingAs($user)
            ->getJson("/api/tenants/{$tenant->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $tenant->id);
    }

    public function test_show_returns_not_found_for_unknown_tenant(): void
    {
        $id = (string) Str::uuid();

        $response = $this->getJson(
            "/api/tenants/{$id}",
        );

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Tenant not found.',
            ]);
    }

    public function test_can_update_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->superAdmin()
            ->create();

        $response = $this
            ->actingAs($user)
            ->putJson(
                "/api/tenants/{$tenant->id}",
                [
                    'name' => 'Updated Tenant',
                ],
            );

        $response->assertOk()
            ->assertJsonPath(
                'data.name',
                'Updated Tenant',
            );

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Tenant',
        ]);
    }

    public function test_update_returns_not_found_for_unknown_tenant(): void
    {
        $id = (string) Str::uuid();

        $response = $this->putJson(
            "/api/tenants/{$id}",
            [
                'name' => 'Updated Tenant',
            ],
        );

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Tenant not found.',
            ]);
    }

    public function test_can_delete_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->superAdmin()
            ->create();

        $response = $this
            ->actingAs($user)
            ->deleteJson(
                "/api/tenants/{$tenant->id}",
            );

        $response->assertOk()
            ->assertJson([
                'message' => 'Tenant deleted successfully.',
            ]);

        $this->assertSoftDeleted('tenants', [
            'id' => $tenant->id,
        ]);
    }

    public function test_delete_returns_not_found_for_unknown_tenant(): void
    {
        $id = (string) Str::uuid();

        $response = $this->deleteJson(
            "/api/tenants/{$id}",
        );

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Tenant not found.',
            ]);
    }

    public function test_can_restore_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $tenant->delete();

        $user = User::factory()
            ->superAdmin()
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson(
                "/api/tenants/{$tenant->id}/restore",
            );

        $response->assertOk()
            ->assertJsonPath('data.id', $tenant->id);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'deleted_at' => null,
        ]);
    }

    public function test_restore_returns_not_found_for_unknown_tenant(): void
    {
        $id = (string) Str::uuid();

        $response = $this->postJson(
            "/api/tenants/{$id}/restore",
        );

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Tenant not found.',
            ]);
    }
}