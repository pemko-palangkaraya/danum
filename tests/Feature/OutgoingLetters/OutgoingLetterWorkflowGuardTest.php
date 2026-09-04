<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\SignerCertificate;
use App\Models\User;
use App\Services\DocxPdfService;
use App\Services\OutgoingLetterService;
use App\Services\OutgoingLetterWorkflowService;
use App\Services\PdfSigningService;
use App\Services\SignerPinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterWorkflowGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(DocxPdfService::class, function ($mock): void { $mock->shouldReceive('convert')->andReturn('outgoing-letters/test/unsigned.pdf'); });
        $this->mock(PdfSigningService::class, function ($mock): void { $mock->shouldReceive('sign')->andReturn('outgoing-letters/test/signed.pdf'); });
    }

    public function test_validation_changes_status_to_validated(): void
    {
        $validator = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::DRAFT,
            'submitted_at' => now(),
            'validator_user_id' => $validator->id,
        ]);

        app(OutgoingLetterWorkflowService::class)->validate($letter, $validator->id, 'Saya sudah memeriksa surat.');
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
        app(OutgoingLetterWorkflowService::class)->validate($letter, $otherUser->id, 'Saya sudah memeriksa surat.');
    }

    public function test_issue_requires_validated_status(): void
    {
        $signer = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::DRAFT,
            'signer_user_id' => $signer->id,
        ]);

        $this->expectException(\DomainException::class);
        app(OutgoingLetterService::class)->issue($letter, $signer->id, 'Saya menyetujui penerbitan surat.', '123456');
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
        app(OutgoingLetterService::class)->issue($letter, $otherUser->id, 'Saya menyetujui penerbitan surat.', '123456');
    }

    public function test_assigned_signer_can_issue_validated_letter_with_note(): void
    {
        $signer = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::VALIDATED,
            'signer_user_id' => $signer->id,
        ]);
        $this->prepareSignerCredentials($letter, $signer);

        app(OutgoingLetterService::class)->issue(
            $letter->fresh(),
            $signer->id,
            'Saya menyetujui penerbitan surat.',
            '123456',
        );
        $letter->refresh();

        $this->assertSame(OutgoingLetterStatus::ISSUED, $letter->status);
        $this->assertSame('Saya menyetujui penerbitan surat.', $letter->signing_note);
    }

    private function prepareSignerCredentials(OutgoingLetter $letter, User $signer): void
    {
        $position = Position::factory()->signatory()->create();
        PositionHolder::factory()->create([
            'position_id' => $position->id,
            'tenant_id' => $letter->tenant_id,
            'user_id' => $signer->id,
            'started_at' => now()->subDay(),
            'ended_at' => null,
        ]);
        $letter->forceFill([
            'signer_position_id' => $position->id,
            'generated_docx_path' => 'outgoing-letters/test/source.docx',
        ])->save();
        app(SignerPinService::class)->set($signer, '123456');
        $certificate = SignerCertificate::query()->create([
            'position_id' => $position->id,
            'user_id' => $signer->id,
            'type' => 'self_signed',
            'serial_number' => 'TEST-'.strtoupper(bin2hex(random_bytes(8))),
            'fingerprint_sha256' => hash('sha256', $signer->id.'-'.$position->id.'-'.microtime(true)),
            'certificate_pem' => 'TEST CERTIFICATE',
            'private_key_encrypted' => 'TEST PRIVATE KEY',
            'valid_from' => now()->subMinute(),
            'valid_until' => now()->addYear(),
            'revoked_at' => null,
            'is_active' => true,
            'generated_by' => $signer->id,
        ]);
        $this->assertTrue($certificate->fresh()->isUsable());
        $letter->forceFill(['signature_certificate_id' => $certificate->id])->save();
    }
}
