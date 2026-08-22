<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_issued_letter_cannot_be_updated_or_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'status' => OutgoingLetterStatus::ISSUED,
            'verification_token' => 'issued-token',
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this
            ->actingAs($user)
            ->putJson("/api/outgoing-letters/{$letter->id}", [
                'subject' => 'Tidak boleh berubah',
            ])
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->deleteJson("/api/outgoing-letters/{$letter->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $letter->id,
            'subject' => $letter->subject,
            'status' => OutgoingLetterStatus::ISSUED->value,
        ]);
    }

    public function test_issued_letter_cannot_be_revalidated_reissued_or_cancelled(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'status' => OutgoingLetterStatus::ISSUED,
            'verification_token' => 'issued-token',
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this
            ->actingAs($user)
            ->postJson("/api/outgoing-letters/{$letter->id}/validate")
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->postJson("/api/outgoing-letters/{$letter->id}/issue")
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->postJson("/api/outgoing-letters/{$letter->id}/cancel")
            ->assertForbidden();
    }

    public function test_editing_validated_letter_returns_it_to_draft(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'status' => OutgoingLetterStatus::VALIDATED,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this
            ->actingAs($user)
            ->putJson("/api/outgoing-letters/{$letter->id}", [
                'subject' => 'Perihal baru',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', OutgoingLetterStatus::DRAFT->value)
            ->assertJsonPath('data.subject', 'Perihal baru');
    }
}
