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

class OutgoingLetterVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_issued_letter_gets_a_verification_token(): void
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
            'status' => OutgoingLetterStatus::VALIDATED,
        ]);

        $this->actingAs($user)
            ->postJson('/api/outgoing-letters/'.$letter->id.'/issue')
            ->assertOk();

        $letter->refresh();
        $this->assertSame(OutgoingLetterStatus::ISSUED, $letter->status);
        $this->assertNotEmpty($letter->verification_token);
    }

    public function test_public_verification_returns_limited_issued_letter_data(): void
    {
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::ISSUED,
            'verification_token' => 'test-verification-token',
        ]);

        $this->getJson('/api/verify/test-verification-token')
            ->assertOk()
            ->assertJsonPath('verified', true)
            ->assertJsonPath('data.number', $letter->number)
            ->assertJsonMissingPath('data.content');
    }

    public function test_public_verification_rejects_unknown_token(): void
    {
        $this->getJson('/api/verify/unknown-token')
            ->assertNotFound()
            ->assertJsonPath('verified', false);
    }
}
