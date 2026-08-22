<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterStatusHistory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_create_and_list_own_outgoing_letters(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/outgoing-letters', $this->payload($letterType));

        $response
            ->assertCreated()
            ->assertJsonPath('data.number', '001/SK/2026')
            ->assertJsonPath('data.tenant_id', $tenant->id);

        $this
            ->actingAs($user)
            ->getJson('/api/outgoing-letters')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_letter_content_is_generated_from_the_letter_type_template(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Kelurahan Danum']);
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
            'body_template' => 'Nomor {{number}} untuk {{recipient_name}} dari {{tenant_name}}.',
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/outgoing-letters', [
                ...$this->payload($letterType),
                'content' => null,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.content',
                'Nomor 001/SK/2026 untuk Budi Santoso dari Kelurahan Danum.',
            );
    }

    public function test_tenant_user_cannot_list_or_view_another_tenants_letters(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $otherLetterType = LetterType::factory()->create([
            'tenant_id' => $otherTenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $otherLetter = OutgoingLetter::factory()->create([
            'tenant_id' => $otherTenant->id,
            'letter_type_id' => $otherLetterType->id,
        ]);
        $user = User::factory()->tenantUser($ownTenant)->create();

        $this
            ->actingAs($user)
            ->getJson('/api/outgoing-letters')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this
            ->actingAs($user)
            ->getJson("/api/outgoing-letters/{$otherLetter->id}")
            ->assertNotFound();
    }

    public function test_only_active_letter_type_from_the_same_tenant_can_be_used(): void
    {
        $tenant = Tenant::factory()->create();
        $inactiveLetterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::DRAFT,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/outgoing-letters', $this->payload($inactiveLetterType));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['letter_type_id']);
    }

    public function test_letter_number_is_unique_per_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'number' => '001/SK/2026',
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this
            ->actingAs($user)
            ->postJson('/api/outgoing-letters', $this->payload($letterType))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['number']);
    }

    public function test_tenant_id_cannot_be_supplied_in_payload(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this
            ->actingAs($user)
            ->postJson('/api/outgoing-letters', [
                ...$this->payload($letterType),
                'tenant_id' => $otherTenant->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_tenant_user_can_update_delete_and_restore_own_letter(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this
            ->actingAs($user)
            ->putJson("/api/outgoing-letters/{$letter->id}", [
                'subject' => 'Perihal Diperbarui',
            ])
            ->assertOk()
            ->assertJsonPath('data.subject', 'Perihal Diperbarui');

        $this
            ->actingAs($user)
            ->deleteJson("/api/outgoing-letters/{$letter->id}")
            ->assertOk();

        $this->assertSoftDeleted('outgoing_letters', ['id' => $letter->id]);

        $this
            ->actingAs($user)
            ->postJson("/api/outgoing-letters/{$letter->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.id', $letter->id);
    }

    public function test_outgoing_letter_follows_validation_and_issuance_workflow(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();
        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'status' => OutgoingLetterStatus::DRAFT,
        ]);
        OutgoingLetterStatusHistory::factory()->create([
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::DRAFT,
            'action' => 'created',
        ]);

        $this
            ->actingAs($user)
            ->postJson("/api/outgoing-letters/{$letter->id}/issue")
            ->assertUnprocessable();

        $this
            ->actingAs($user)
            ->postJson("/api/outgoing-letters/{$letter->id}/validate")
            ->assertOk()
            ->assertJsonPath('data.status', OutgoingLetterStatus::VALIDATED->value);

        $this
            ->actingAs($user)
            ->postJson("/api/outgoing-letters/{$letter->id}/issue")
            ->assertOk()
            ->assertJsonPath('data.status', OutgoingLetterStatus::ISSUED->value);

        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $letter->id,
            'status' => OutgoingLetterStatus::ISSUED->value,
        ]);

        $this->assertSame(now()->toDateString(), $letter->fresh()->issued_at->toDateString());
        $this->assertDatabaseCount('outgoing_letter_status_histories', 3);
        $this->assertDatabaseHas('outgoing_letter_status_histories', [
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::ISSUED->value,
            'action' => 'issued',
        ]);

        $this
            ->actingAs($user)
            ->getJson("/api/outgoing-letters/{$letter->id}/history")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.2.action', 'issued');
    }

    public function test_outgoing_letter_status_cannot_be_updated_directly(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this
            ->actingAs($user)
            ->putJson("/api/outgoing-letters/{$letter->id}", [
                'status' => OutgoingLetterStatus::ISSUED->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_tenant_user_cannot_download_outgoing_letter_without_generated_docx(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'generated_docx_path' => null,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this
            ->actingAs($user)
            ->get("/api/outgoing-letters/{$letter->id}/pdf")
            ->assertStatus(422);
    }

    private function payload(LetterType $letterType): array
    {
        return [
            'letter_type_id' => $letterType->id,
            'number' => '001/SK/2026',
            'recipient_name' => 'Budi Santoso',
            'recipient_address' => 'Palangka Raya',
            'subject' => 'Surat Keterangan',
            'content' => 'Isi surat keterangan.',
            'status' => OutgoingLetterStatus::DRAFT->value,
        ];
    }
}
