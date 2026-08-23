<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterStatusHistory;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_create_and_list_own_outgoing_letters(): void
    {
        $tenant = Tenant::factory()->create(); $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => LetterTypeStatus::ACTIVE]); $user = User::factory()->tenantUser($tenant)->create();
        $response = $this->actingAs($user)->postJson('/api/outgoing-letters', $this->payload($letterType));
        $response->assertCreated()->assertJsonPath('data.number', '001/SK/2026')->assertJsonPath('data.tenant_id', $tenant->id);
        $this->actingAs($user)->getJson('/api/outgoing-letters')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_letter_content_is_generated_from_the_letter_type_template(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Kelurahan Danum']); $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => LetterTypeStatus::ACTIVE, 'body_template' => 'Nomor {{number}} untuk {{recipient_name}} dari {{tenant_name}}.']); $user = User::factory()->tenantUser($tenant)->create();
        $response = $this->actingAs($user)->postJson('/api/outgoing-letters', [...$this->payload($letterType), 'content' => null]);
        $response->assertCreated()->assertJsonPath('data.content', 'Nomor 001/SK/2026 untuk Budi Santoso dari Kelurahan Danum.');
    }

    public function test_tenant_user_cannot_list_or_view_another_tenants_letters(): void
    {
        $ownTenant = Tenant::factory()->create(); $otherTenant = Tenant::factory()->create(); $otherLetterType = LetterType::factory()->create(['tenant_id' => null, 'status' => LetterTypeStatus::ACTIVE]); $otherLetter = OutgoingLetter::factory()->create(['tenant_id' => $otherTenant->id, 'letter_type_id' => $otherLetterType->id]); $user = User::factory()->tenantUser($ownTenant)->create();
        $this->actingAs($user)->getJson('/api/outgoing-letters')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($user)->getJson("/api/outgoing-letters/{$otherLetter->id}")->assertNotFound();
    }

    public function test_only_active_letter_type_from_the_same_tenant_can_be_used(): void
    {
        $tenant = Tenant::factory()->create(); $inactiveLetterType = LetterType::factory()->create(['tenant_id' => null, 'status' => LetterTypeStatus::DRAFT]); $user = User::factory()->tenantUser($tenant)->create();
        $this->actingAs($user)->postJson('/api/outgoing-letters', $this->payload($inactiveLetterType))->assertUnprocessable()->assertJsonValidationErrors(['letter_type_id']);
    }

    public function test_letter_number_is_unique_per_tenant(): void
    {
        $tenant = Tenant::factory()->create(); $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => LetterTypeStatus::ACTIVE]); OutgoingLetter::factory()->create(['tenant_id' => $tenant->id, 'letter_type_id' => $letterType->id, 'number' => '001/SK/2026']); $user = User::factory()->tenantUser($tenant)->create();
        $this->actingAs($user)->postJson('/api/outgoing-letters', $this->payload($letterType))->assertUnprocessable()->assertJsonValidationErrors(['number']);
    }

    public function test_tenant_id_cannot_be_supplied_in_payload(): void
    {
        $tenant = Tenant::factory()->create(); $otherTenant = Tenant::factory()->create(); $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => LetterTypeStatus::ACTIVE]); $user = User::factory()->tenantUser($tenant)->create();
        $this->actingAs($user)->postJson('/api/outgoing-letters', [...$this->payload($letterType), 'tenant_id' => $otherTenant->id])->assertUnprocessable()->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_tenant_user_can_update_delete_and_super_admin_can_restore_own_letter(): void
    {
        $tenant = Tenant::factory()->create(); $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => LetterTypeStatus::ACTIVE]); $letter = OutgoingLetter::factory()->create(['tenant_id' => $tenant->id, 'letter_type_id' => $letterType->id]); $user = User::factory()->tenantUser($tenant)->create();
        $this->actingAs($user)->putJson("/api/outgoing-letters/{$letter->id}", ['subject' => 'Perihal Diperbarui'])->assertOk()->assertJsonPath('data.subject', 'Perihal Diperbarui');
        $this->actingAs($user)->deleteJson("/api/outgoing-letters/{$letter->id}")->assertOk();
        $this->assertSoftDeleted('outgoing_letters', ['id' => $letter->id]);
        $this->actingAs($user)->postJson("/api/outgoing-letters/{$letter->id}/restore")->assertForbidden();
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin)->postJson("/api/outgoing-letters/{$letter->id}/restore")->assertOk()->assertJsonPath('data.id', $letter->id);
    }

    public function test_only_assigned_validator_can_validate_and_assigned_signer_can_issue(): void
    {
        $tenant = Tenant::factory()->create(); $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => LetterTypeStatus::ACTIVE]);
        $creator = User::factory()->tenantUser($tenant)->create(); $validator = User::factory()->tenantUser($tenant)->create(); $signer = User::factory()->tenantUser($tenant)->create();
        $validatorPosition = Position::factory()->create(['tenant_id' => $tenant->id, 'can_validate' => true]); $signerPosition = Position::factory()->create(['tenant_id' => $tenant->id, 'can_sign' => true]);
        PositionHolder::factory()->create(['position_id' => $validatorPosition->id, 'user_id' => $validator->id, 'started_at' => now()->subDay()]); PositionHolder::factory()->create(['position_id' => $signerPosition->id, 'user_id' => $signer->id, 'started_at' => now()->subDay()]);
        $letter = OutgoingLetter::factory()->create(['tenant_id' => $tenant->id, 'letter_type_id' => $letterType->id, 'status' => OutgoingLetterStatus::DRAFT, 'validator_position_id' => $validatorPosition->id, 'validator_user_id' => $validator->id, 'validator_name' => $validator->name, 'validator_title' => $validatorPosition->name, 'signer_position_id' => $signerPosition->id, 'signer_user_id' => $signer->id, 'signer_name' => $signer->name, 'signer_title' => $signerPosition->name]);
        OutgoingLetterStatusHistory::factory()->create(['outgoing_letter_id' => $letter->id, 'changed_by' => $creator->id, 'status' => OutgoingLetterStatus::DRAFT, 'action' => 'created']);

        $this->actingAs($creator)->postJson("/api/outgoing-letters/{$letter->id}/validate")->assertForbidden();
        $this->actingAs($creator)->postJson("/api/outgoing-letters/{$letter->id}/issue")->assertForbidden();
        $this->actingAs($validator)->postJson("/api/outgoing-letters/{$letter->id}/validate")->assertOk()->assertJsonPath('data.status', OutgoingLetterStatus::VALIDATED->value);
        $this->actingAs($validator)->postJson("/api/outgoing-letters/{$letter->id}/issue")->assertForbidden();
        $this->actingAs($signer)->postJson("/api/outgoing-letters/{$letter->id}/issue")->assertOk()->assertJsonPath('data.status', OutgoingLetterStatus::ISSUED->value);
    }

    public function test_outgoing_letter_status_cannot_be_updated_directly(): void
    {
        $tenant = Tenant::factory()->create(); $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => LetterTypeStatus::ACTIVE]); $letter = OutgoingLetter::factory()->create(['tenant_id' => $tenant->id, 'letter_type_id' => $letterType->id]); $user = User::factory()->tenantUser($tenant)->create();
        $this->actingAs($user)->putJson("/api/outgoing-letters/{$letter->id}", ['status' => OutgoingLetterStatus::ISSUED->value])->assertUnprocessable()->assertJsonValidationErrors(['status']);
    }

    public function test_tenant_user_cannot_download_outgoing_letter_without_generated_docx(): void
    {
        $tenant = Tenant::factory()->create(); $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => LetterTypeStatus::ACTIVE]); $letter = OutgoingLetter::factory()->create(['tenant_id' => $tenant->id, 'letter_type_id' => $letterType->id, 'generated_docx_path' => null]); $user = User::factory()->tenantUser($tenant)->create();
        $this->actingAs($user)->get("/api/outgoing-letters/{$letter->id}/pdf")->assertStatus(422);
    }

    private function payload(LetterType $letterType): array
    {
        return ['letter_type_id' => $letterType->id, 'number' => '001/SK/2026', 'recipient_name' => 'Budi Santoso', 'recipient_address' => 'Palangka Raya', 'subject' => 'Surat Keterangan', 'content' => 'Isi surat keterangan.', 'status' => OutgoingLetterStatus::DRAFT->value];
    }
}
