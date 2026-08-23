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
        LetterType::factory()->count(3)->create(['tenant_id' => null]);
        LetterType::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
        $user = User::factory()->superAdmin()->create();

        $response = $this->actingAs($user)->getJson('/api/letter-types');

        $response->assertOk()->assertJsonCount(3, 'data');
        $response->assertJsonMissing(['tenant_id' => LetterType::query()->whereNotNull('tenant_id')->firstOrFail()->tenant_id]);
    }

    public function test_can_create_letter_type(): void
    {
        $user = User::factory()->superAdmin()->create();
        $data = [
            'code' => 'SK001',
            'name' => 'Surat Keterangan',
            'description' => 'Surat keterangan test',
            'status' => LetterTypeStatus::DRAFT->value,
        ];

        $response = $this->actingAs($user)->postJson('/api/letter-types', $data);

        $response->assertCreated()->assertJsonPath('data.code', 'SK001')->assertJsonPath('data.name', 'Surat Keterangan');
        $this->assertDatabaseHas('letter_types', ['tenant_id' => null, 'code' => 'SK001', 'name' => 'Surat Keterangan']);
    }

    public function test_can_show_letter_type(): void
    {
        $letterType = LetterType::factory()->create(['tenant_id' => null]);
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->getJson("/api/letter-types/{$letterType->id}")
            ->assertOk()->assertJsonPath('data.id', $letterType->id);
    }

    public function test_show_returns_not_found_for_unknown_letter_type(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user)->getJson('/api/letter-types/'.Str::uuid())
            ->assertNotFound()->assertJson(['message' => 'Letter type not found.']);
    }

    public function test_can_update_letter_type(): void
    {
        $letterType = LetterType::factory()->create(['tenant_id' => null]);
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->putJson("/api/letter-types/{$letterType->id}", ['name' => 'Updated Letter Type'])
            ->assertOk()->assertJsonPath('data.name', 'Updated Letter Type');
        $this->assertDatabaseHas('letter_types', ['id' => $letterType->id, 'name' => 'Updated Letter Type', 'tenant_id' => null]);
    }

    public function test_update_returns_not_found_for_unknown_letter_type(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user)->putJson('/api/letter-types/'.Str::uuid(), ['name' => 'Updated Letter Type'])
            ->assertNotFound()->assertJson(['message' => 'Letter type not found.']);
    }

    public function test_can_delete_letter_type(): void
    {
        $letterType = LetterType::factory()->create(['tenant_id' => null]);
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->deleteJson("/api/letter-types/{$letterType->id}")
            ->assertOk()->assertJson(['message' => 'Letter type deleted successfully.']);
        $this->assertSoftDeleted('letter_types', ['id' => $letterType->id]);
    }

    public function test_delete_returns_not_found_for_unknown_letter_type(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user)->deleteJson('/api/letter-types/'.Str::uuid())
            ->assertNotFound()->assertJson(['message' => 'Letter type not found.']);
    }

    public function test_can_restore_letter_type(): void
    {
        $letterType = LetterType::factory()->create(['tenant_id' => null]);
        $letterType->delete();
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->postJson("/api/letter-types/{$letterType->id}/restore")
            ->assertOk()->assertJsonPath('data.id', $letterType->id);
        $this->assertDatabaseHas('letter_types', ['id' => $letterType->id, 'deleted_at' => null]);
    }

    public function test_restore_returns_not_found_for_unknown_letter_type(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user)->postJson('/api/letter-types/'.Str::uuid().'/restore')
            ->assertNotFound()->assertJson(['message' => 'Letter type not found.']);
    }

    public function test_tenant_user_cannot_access_letter_type_management(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create(['tenant_id' => null]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this->actingAs($user)->getJson('/api/letter-types')->assertForbidden();
        $this->actingAs($user)->getJson("/api/letter-types/{$letterType->id}")->assertForbidden();
        $this->actingAs($user)->postJson('/api/letter-types', [
            'code' => 'SK001', 'name' => 'Surat Keterangan', 'status' => LetterTypeStatus::DRAFT->value,
        ])->assertForbidden();
        $this->actingAs($user)->putJson("/api/letter-types/{$letterType->id}", ['name' => 'Updated'])->assertForbidden();
        $this->actingAs($user)->deleteJson("/api/letter-types/{$letterType->id}")->assertForbidden();
        $this->actingAs($user)->postJson("/api/letter-types/{$letterType->id}/restore")->assertForbidden();
    }

    public function test_invalid_letter_type_payload_returns_validation_errors(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user)->postJson('/api/letter-types', [
            'code' => '', 'name' => '', 'status' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors(['code', 'name', 'status']);
    }
}
