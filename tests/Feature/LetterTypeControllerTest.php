<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LetterTypeStatus;
use App\Models\LetterType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LetterTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_letter_types(): void
    {
        $tenant = Tenant::factory()->create();

        LetterType::factory()
            ->count(3)
            ->create([
                'tenant_id' => $tenant->id,
            ]);

        LetterType::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/api/letter-types');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing([
                'tenant_id' => LetterType::query()
                    ->where('tenant_id', '!=', $tenant->id)
                    ->firstOrFail()
                    ->tenant_id,
            ]);
    }

    public function test_can_create_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $data = [
            'code' => 'SK001',
            'name' => 'Surat Keterangan',
            'description' => 'Surat keterangan test',
            'status' => LetterTypeStatus::DRAFT->value,
        ];

        $response = $this
            ->actingAs($user)
            ->postJson('/api/letter-types', $data);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'SK001')
            ->assertJsonPath('data.name', 'Surat Keterangan');

        $this->assertDatabaseHas('letter_types', [
            'tenant_id' => $tenant->id,
            'code' => 'SK001',
            'name' => 'Surat Keterangan',
        ]);
    }

    public function test_can_show_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->getJson("/api/letter-types/{$letterType->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $letterType->id);
    }

    public function test_show_returns_not_found_for_unknown_letter_type(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $id = (string) Str::uuid();

        $response = $this
            ->actingAs($user)
            ->getJson("/api/letter-types/{$id}");

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Letter type not found.',
            ]);
    }

    public function test_can_update_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->putJson(
                "/api/letter-types/{$letterType->id}",
                [
                    'name' => 'Updated Letter Type',
                ],
            );

        $response->assertOk()
            ->assertJsonPath(
                'data.name',
                'Updated Letter Type',
            );

        $this->assertDatabaseHas('letter_types', [
            'id' => $letterType->id,
            'name' => 'Updated Letter Type',
        ]);
    }

    public function test_update_returns_not_found_for_unknown_letter_type(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $id = (string) Str::uuid();

        $response = $this
            ->actingAs($user)
            ->putJson("/api/letter-types/{$id}", [
                'name' => 'Updated Letter Type',
            ]);

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Letter type not found.',
            ]);
    }

    public function test_can_delete_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->deleteJson(
                "/api/letter-types/{$letterType->id}",
            );

        $response->assertOk()
            ->assertJson([
                'message' => 'Letter type deleted successfully.',
            ]);

        $this->assertSoftDeleted('letter_types', [
            'id' => $letterType->id,
        ]);
    }

    public function test_delete_returns_not_found_for_unknown_letter_type(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $id = (string) Str::uuid();

        $response = $this
            ->actingAs($user)
            ->deleteJson("/api/letter-types/{$id}");

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Letter type not found.',
            ]);
    }

    public function test_can_restore_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $letterType->delete();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson(
                "/api/letter-types/{$letterType->id}/restore",
            );

        $response->assertOk()
            ->assertJsonPath('data.id', $letterType->id);

        $this->assertDatabaseHas('letter_types', [
            'id' => $letterType->id,
            'deleted_at' => null,
        ]);
    }

    public function test_restore_returns_not_found_for_unknown_letter_type(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $id = (string) Str::uuid();

        $response = $this
            ->actingAs($user)
            ->postJson("/api/letter-types/{$id}/restore");

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Letter type not found.',
            ]);
    }

    public function test_tenant_user_cannot_access_another_tenants_letter_type(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);
        $user = User::factory()->tenantUser($ownTenant)->create();

        $response = $this
            ->actingAs($user)
            ->getJson("/api/letter-types/{$letterType->id}");

        $response->assertNotFound();
    }

    public function test_tenant_id_cannot_be_supplied_when_creating_letter_type(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($ownTenant)->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/letter-types', [
                'tenant_id' => $otherTenant->id,
                'code' => 'SK001',
                'name' => 'Surat Keterangan',
                'status' => LetterTypeStatus::DRAFT->value,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_tenant_id_cannot_be_changed_when_updating_letter_type(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $ownTenant->id,
        ]);
        $user = User::factory()->tenantUser($ownTenant)->create();

        $response = $this
            ->actingAs($user)
            ->putJson("/api/letter-types/{$letterType->id}", [
                'tenant_id' => $otherTenant->id,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);

        $this->assertDatabaseHas('letter_types', [
            'id' => $letterType->id,
            'tenant_id' => $ownTenant->id,
        ]);
    }

    public function test_invalid_letter_type_payload_returns_validation_errors(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/letter-types', [
                'code' => '',
                'name' => '',
                'status' => 'invalid',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'name', 'status']);
    }
}
