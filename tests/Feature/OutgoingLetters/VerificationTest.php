<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterWithdrawalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_verification_shows_active_for_current_issued_letter(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 10:20:00', 'Asia/Pontianak'));
        $type = LetterType::factory()->create([
            'status' => LetterTypeStatus::ACTIVE,
            'has_expiry' => true,
            'validity_period' => '6_months',
        ]);
        $letter = OutgoingLetter::factory()->create([
            'letter_type_id' => $type->id,
            'status' => OutgoingLetterStatus::ISSUED,
            'valid_from' => now(),
            'valid_until' => now()->addMonths(6),
        ]);

        $this->get('/verify/'.$letter->verification_token)
            ->assertOk()
            ->assertSee('Aktif / Valid');

        Carbon::setTestNow();
    }

    public function test_public_verification_shows_expired_for_issued_expired_letter(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:20:00', 'Asia/Pontianak'));
        $type = LetterType::factory()->create([
            'status' => LetterTypeStatus::ACTIVE,
            'has_expiry' => true,
            'validity_period' => '1_week',
        ]);
        $letter = OutgoingLetter::factory()->create([
            'letter_type_id' => $type->id,
            'status' => OutgoingLetterStatus::ISSUED,
            'valid_from' => Carbon::parse('2026-08-25 10:20:00', 'Asia/Pontianak'),
            'valid_until' => Carbon::parse('2026-09-01 10:20:00', 'Asia/Pontianak'),
        ]);

        $this->get('/verify/'.$letter->verification_token)
            ->assertOk()
            ->assertSee('Kedaluwarsa');

        Carbon::setTestNow();
    }

    public function test_public_verification_shows_withdrawn_for_withdrawn_letter(): void
    {
        $requester = User::factory()->superAdmin()->create();
        $decider = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create(['status' => OutgoingLetterStatus::WITHDRAWN]);
        OutgoingLetterWithdrawalRequest::query()->create([
            'outgoing_letter_id' => $letter->id,
            'requested_by' => $requester->id,
            'requested_at' => now()->subHour(),
            'reason' => 'Surat dibatalkan.',
            'statement_path' => 'withdrawals/statement.pdf',
            'status' => OutgoingLetterWithdrawalStatus::APPROVED,
            'decided_by' => $decider->id,
            'decided_at' => now(),
            'decision_note' => 'Penarikan disetujui.',
        ]);

        $this->get('/verify/'.$letter->verification_token)
            ->assertOk()
            ->assertSee('Ditarik')
            ->assertSee('Penarikan disetujui.');
    }

    public function test_public_verification_hides_unissued_or_unknown_documents(): void
    {
        $draft = OutgoingLetter::factory()->create(['status' => OutgoingLetterStatus::DRAFT]);

        $this->get('/verify/'.$draft->verification_token)
            ->assertOk()
            ->assertSee('Dokumen Tidak Terverifikasi');

        $this->get('/verify/unknown-token-for-danum')
            ->assertOk()
            ->assertSee('Dokumen Tidak Terverifikasi');
    }
}
