<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterWorkflowGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_changes_status_to_validated(): void
    {
        $validator = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::DRAFT,
            'submitted_at' => now(),
            'validator_user_id' => $validator->id,
        ]);

        app(OutgoingLetterService::class)->validate($letter, $validator->id, 'Saya sudah memeriksa surat.');
        $letter->refresh();

        $this->assertSame(OutgoingLetterStatus::VALIDATED, $letter->status);
        $this->assertNull($letter->submitted_at);
        $this->assertSame('Saya sudah memeriksa surat.', $letter->verification_note);
    }

    public function test_validation_requires_assigned_validator(): void
    {
        $validator = User::factory()->superAdmin()->create();
        $otherUser = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::DRAFT,
            'submitted_at' => now(),
            'validator_user_id' => $validator->id,
        ]);

        $this->expectException(\DomainException::class);
        app(OutgoingLetterService::class)->validate($letter, $otherUser->id, 'Saya sudah memeriksa surat.');
    }

    public function test_issue_requires_validated_status(): void
    {
        $signer = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::DRAFT,
            'signer_user_id' => $signer->id,
        ]);

        $this->expectException(\DomainException::class);
        app(OutgoingLetterService::class)->issue($letter, $signer->id, 'Saya menyetujui penerbitan surat.');
    }

    public function test_issue_requires_assigned_signer(): void
    {
        $signer = User::factory()->superAdmin()->create();
        $otherUser = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::VALIDATED,
            'signer_user_id' => $signer->id,
        ]);

        $this->expectException(\DomainException::class);
        app(OutgoingLetterService::class)->issue($letter, $otherUser->id, 'Saya menyetujui penerbitan surat.');
    }

    public function test_assigned_signer_can_issue_validated_letter_with_note(): void
    {
        $signer = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::VALIDATED,
            'signer_user_id' => $signer->id,
        ]);

        app(OutgoingLetterService::class)->issue($letter, $signer->id, 'Saya menyetujui penerbitan surat.');
        $letter->refresh();

        $this->assertSame(OutgoingLetterStatus::ISSUED, $letter->status);
        $this->assertSame('Saya menyetujui penerbitan surat.', $letter->signing_note);
    }
}
