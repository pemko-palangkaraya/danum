<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\DocxPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SignerPassphraseIssueAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_rejects_short_passphrase_before_pdf_conversion(): void
    {
        Storage::fake('local');

        $signer = User::factory()->superAdmin()->create();

        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::VALIDATED,
            'signer_user_id' => $signer->id,
            'generated_docx_path' => 'outgoing-letters/test/source.docx',
        ]);
        Storage::disk('local')->put($letter->generated_docx_path, 'test docx content');

        $this->mock(DocxPdfService::class, function ($mock): void {
            $mock->shouldReceive('convert')->never();
        });

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Passphrase penanda tangan minimal 8 karakter.');

        app(\App\Services\OutgoingLetterService::class)->issue(
            $letter,
            $signer->id,
            'Saya menyetujui penerbitan surat.',
            'short',
        );
    }

    public function test_issue_rejects_passphrase_shorter_than_eight_characters(): void
    {
        Storage::fake('local');

        $signer = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::VALIDATED,
            'signer_user_id' => $signer->id,
            'generated_docx_path' => 'outgoing-letters/test/source.docx',
        ]);
        Storage::disk('local')->put($letter->generated_docx_path, 'test docx content');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('PIN penandatangan tidak valid.');

        app(\App\Services\OutgoingLetterService::class)->issue(
            $letter,
            $signer->id,
            'Saya menyetujui penerbitan surat.',
            '1234567',
        );
    }
}
