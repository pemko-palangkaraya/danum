<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LetterTypeStatus;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LetterTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_letter_types(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();

        LetterType::factory()
            ->count(3)
            ->create([
                'tenant_id' => $tenant->id,
            ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/api/letter-types');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_letter_type(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $data = [
            'tenant_id' => $tenant->id,
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
        $tenant = \App\Models\Tenant::factory()->create();

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
        $id = (string) Str::uuid();

        $response = $this->getJson(
            "/api/letter-types/{$id}",
        );

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Letter type not found.',
            ]);
    }

    public function test_can_update_letter_type(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();

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
        $id = (string) Str::uuid();

        $response = $this->putJson(
            "/api/letter-types/{$id}",
            [
                'name' => 'Updated Letter Type',
            ],
        );

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Letter type not found.',
            ]);
    }

    public function test_can_delete_letter_type(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();

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
        $id = (string) Str::uuid();

        $response = $this->deleteJson(
            "/api/letter-types/{$id}",
        );

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Letter type not found.',
            ]);
    }

    public function test_can_restore_letter_type(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();

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
        $id = (string) Str::uuid();

        $response = $this->postJson(
            "/api/letter-types/{$id}/restore",
        );

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Letter type not found.',
            ]);
    }
}
