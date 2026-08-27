<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\User;
use App\Services\DocxPdfService;
use App\Services\OutgoingLetterService;
use App\Services\PdfSigningService;
use App\Services\SignerCertificateService;
use App\Services\SignerPinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterWorkflowGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(DocxPdfService::class, function ($mock): void {
            $mock->shouldReceive('convert')->andReturn('outgoing-letters/test/unsigned.pdf');
        });

        $this->mock(PdfSigningService::class, function ($mock): void {
            $mock->shouldReceive('sign')->andReturn('outgoing-letters/test/signed.pdf');
        });
    }

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

        $this->prepareSignerCredentials($letter, $signer);

        app(OutgoingLetterService::class)->issue($letter->fresh(), $signer->id, 'Saya menyetujui penerbitan surat.', '123456');
        $letter->refresh();

        $this->assertSame(OutgoingLetterStatus::ISSUED, $letter->status);
        $this->assertSame('Saya menyetujui penerbitan surat.', $letter->signing_note);
    }

    private function prepareSignerCredentials(OutgoingLetter $letter, User $signer): void
    {
        $position = Position::factory()->signatory()->create(['tenant_id' => $letter->tenant_id]);
        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $signer->id,
            'started_at' => now()->subDay(),
            'ended_at' => null,
        ]);

        $letter->forceFill([
            'signer_position_id' => $position->id,
            'generated_docx_path' => 'outgoing-letters/test/source.docx',
        ])->save();

        app(SignerPinService::class)->set($signer, '123456');
        app(SignerCertificateService::class)->generate($position, $holder, $signer);
    }
}
