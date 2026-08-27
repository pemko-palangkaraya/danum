<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\DocxPdfService;
use App\Services\SignerPinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignerPinIssueAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_rejects_missing_or_invalid_pin_before_pdf_conversion(): void
    {
        $signer = User::factory()->superAdmin()->create();
        app(SignerPinService::class)->set($signer, '123456');

        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::VALIDATED,
            'signer_user_id' => $signer->id,
        ]);

        $this->mock(DocxPdfService::class, function ($mock): void {
            $mock->shouldReceive('convert')->never();
        });

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('PIN penandatangan tidak valid.');

        app(\App\Services\OutgoingLetterService::class)->issue(
            $letter,
            $signer->id,
            'Saya menyetujui penerbitan surat.',
            '000000',
        );
    }

    public function test_issue_requires_a_configured_pin(): void
    {
        $signer = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::VALIDATED,
            'signer_user_id' => $signer->id,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('PIN penandatangan tidak valid.');

        app(\App\Services\OutgoingLetterService::class)->issue(
            $letter,
            $signer->id,
            'Saya menyetujui penerbitan surat.',
            '123456',
        );
    }
}