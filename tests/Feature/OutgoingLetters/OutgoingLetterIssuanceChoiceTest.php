<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\DocxPdfService;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OutgoingLetterIssuanceChoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_can_stop_before_tte_and_keeps_final_unsigned_pdf(): void
    {
        Storage::fake('local');
        $user = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::VALIDATED,
            'signer_user_id' => $user->id,
            'generated_docx_path' => 'outgoing-letters/test/source.docx',
        ]);

        Storage::disk('local')->put($letter->generated_docx_path, 'test docx');
        $this->mock(DocxPdfService::class, fn ($mock) => $mock->shouldReceive('convert')->once()->andReturn('outgoing-letters/test/final-unsigned.pdf'));

        $issued = app(OutgoingLetterService::class)->issue(
            $letter,
            $user->id,
            'Surat sudah diperiksa dan diterbitkan untuk tanda tangan basah.',
            null,
            false,
        );

        $issued->refresh();
        $this->assertSame(OutgoingLetterStatus::ISSUED, $issued->status);
        $this->assertSame('outgoing-letters/test/final-unsigned.pdf', $issued->unsigned_pdf_path);
        $this->assertNull($issued->signed_pdf_path);
        $this->assertNotNull($issued->verification_token);
        $this->assertDatabaseHas('outgoing_letter_status_histories', [
            'outgoing_letter_id' => $issued->id,
            'action' => 'issued',
            'status' => OutgoingLetterStatus::ISSUED->value,
        ]);
        $this->assertDatabaseMissing('outgoing_letter_status_histories', [
            'outgoing_letter_id' => $issued->id,
            'action' => 'signed',
        ]);
    }
}
