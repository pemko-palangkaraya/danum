<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Models\LetterType;
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
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OutgoingLetterWorkflowTest extends TestCase
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

    public function test_create_records_draft_and_history(): void
    {
        $user = User::factory()->superAdmin()->create(); $source = OutgoingLetter::factory()->create(); $data = $source->only(['tenant_id', 'created_by', 'letter_type_id', 'number', 'recipient_name', 'recipient_address', 'subject', 'content', 'issued_at', 'status']); $data['number'] = fake()->unique()->bothify('###/SK/####'); $service = app(OutgoingLetterService::class);
        $created = $service->create($data, $user->id);
        $this->assertSame(OutgoingLetterStatus::DRAFT, $created->status);
        $this->assertDatabaseHas('outgoing_letter_status_histories', ['outgoing_letter_id' => $created->id, 'changed_by' => $user->id, 'status' => OutgoingLetterStatus::DRAFT->value, 'action' => 'created']);
    }

    public function test_submit_validate_issue_workflow_sets_active_letter_and_history(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 10:20:00', 'Asia/Pontianak')); $user = User::factory()->superAdmin()->create(); $validator = User::factory()->superAdmin()->create(); $type = LetterType::factory()->create(['status' => LetterTypeStatus::ACTIVE, 'validity_period' => '6_months', 'has_expiry' => true]); $letter = OutgoingLetter::factory()->create(['letter_type_id' => $type->id, 'validator_user_id' => $validator->id, 'signer_user_id' => $user->id]); $this->prepareSignerCredentials($letter, $user); $service = app(OutgoingLetterService::class);
        try {
            $service->submit($letter, $user->id); $letter->refresh(); $this->assertNotNull($letter->submitted_at);
            $service->validate($letter, $validator->id, 'Saya telah memeriksa kelengkapan dan kesesuaian surat.'); $letter->refresh(); $this->assertSame(OutgoingLetterStatus::VALIDATED, $letter->status); $this->assertNull($letter->submitted_at); $this->assertSame('Saya telah memeriksa kelengkapan dan kesesuaian surat.', $letter->verification_note);
            $service->issue($letter, $user->id, 'Saya menyetujui dan menandatangani surat ini untuk diterbitkan.', '123456'); $letter->refresh();
            $this->assertSame(OutgoingLetterStatus::ISSUED, $letter->status); $this->assertSame('2026-08-25', $letter->issued_at->toDateString()); $this->assertSame('2026-08-25 10:20:00', $letter->valid_from->format('Y-m-d H:i:s')); $this->assertSame('2027-02-25 10:20:00', $letter->valid_until->format('Y-m-d H:i:s')); $this->assertTrue($letter->isActive()); $this->assertFalse($letter->isExpired()); $this->assertNotNull($letter->verification_token); $this->assertSame('Saya menyetujui dan menandatangani surat ini untuk diterbitkan.', $letter->signing_note);
            $this->assertDatabaseHas('outgoing_letter_status_histories', ['outgoing_letter_id' => $letter->id, 'action' => 'issued', 'status' => OutgoingLetterStatus::ISSUED->value]);
        } finally { Carbon::setTestNow(); }
    }

    public function test_submit_validate_and_issue_require_notes(): void
    {
        $user = User::factory()->superAdmin()->create(); $validator = User::factory()->superAdmin()->create(); $letter = OutgoingLetter::factory()->create(['validator_user_id' => $validator->id, 'signer_user_id' => $user->id]); $service = app(OutgoingLetterService::class); $service->submit($letter, $user->id); $letter->refresh();
        try { $service->validate($letter, $validator->id); $this->fail('Verification without note should fail.'); } catch (\DomainException $exception) { $this->assertSame('Catatan verifikasi wajib diisi.', $exception->getMessage()); }
        $service->validate($letter, $validator->id, 'Verifikasi lengkap.'); $letter->refresh();
        try { $service->issue($letter, $user->id); $this->fail('Signing without note should fail.'); } catch (\DomainException $exception) { $this->assertSame('Catatan penandatanganan wajib diisi.', $exception->getMessage()); }
    }

    public function test_submitted_draft_cannot_be_edited_or_deleted(): void
    {
        $user = User::factory()->superAdmin()->create(); $validator = User::factory()->superAdmin()->create(); $letter = OutgoingLetter::factory()->create(['validator_user_id' => $validator->id]); $service = app(OutgoingLetterService::class); $service->submit($letter, $user->id); $letter->refresh(); $this->expectException(\DomainException::class); $service->update($letter, ['subject' => 'Tidak boleh']);
    }

    public function test_reject_returns_letter_to_draft_with_reason(): void
    {
        $user = User::factory()->superAdmin()->create(); $letter = OutgoingLetter::factory()->create(['status' => OutgoingLetterStatus::VALIDATED]); $service = app(OutgoingLetterService::class); $service->reject($letter, $user->id, 'Data penerima perlu diperbaiki.'); $letter->refresh(); $this->assertSame(OutgoingLetterStatus::DRAFT, $letter->status); $this->assertSame('Data penerima perlu diperbaiki.', $letter->rejection_reason); $this->assertSame($user->id, $letter->rejected_by); $this->assertNotNull($letter->rejected_at);
    }

    public function test_editable_draft_can_be_cancelled(): void
    {
        $user = User::factory()->superAdmin()->create(); $letter = OutgoingLetter::factory()->create(); $service = app(OutgoingLetterService::class); $service->cancel($letter, $user->id); $letter->refresh(); $this->assertSame(OutgoingLetterStatus::CANCELLED, $letter->status); $this->assertDatabaseHas('outgoing_letter_status_histories', ['outgoing_letter_id' => $letter->id, 'action' => 'cancelled']);
    }

    #[DataProvider('validityPeriods')]
    public function test_issue_calculates_valid_until_from_letter_type(string $period, string $expected): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 10:20:00', 'Asia/Pontianak'));
        try { $user = User::factory()->superAdmin()->create(); $type = LetterType::factory()->create(['status' => LetterTypeStatus::ACTIVE, 'validity_period' => $period, 'has_expiry' => $period !== 'none']); $letter = OutgoingLetter::factory()->create(['letter_type_id' => $type->id, 'status' => OutgoingLetterStatus::VALIDATED, 'signer_user_id' => $user->id]); $this->prepareSignerCredentials($letter, $user); app(OutgoingLetterService::class)->issue($letter->fresh(), $user->id, 'Saya menandatangani dan menyetujui penerbitan surat ini.', '123456'); $letter->refresh(); if ($period === 'none') $this->assertNull($letter->valid_until); else $this->assertSame($expected, $letter->valid_until->format('Y-m-d H:i:s')); } finally { Carbon::setTestNow(); }
    }

    public static function validityPeriods(): array { return ['none' => ['none', ''], 'one week' => ['1_week', '2026-09-01 10:20:00'], 'two weeks' => ['2_weeks', '2026-09-08 10:20:00'], 'one month' => ['1_month', '2026-09-25 10:20:00'], 'three months' => ['3_months', '2026-11-25 10:20:00'], 'six months' => ['6_months', '2027-02-25 10:20:00'], 'one year' => ['1_year', '2027-08-25 10:20:00']]; }

    public function test_expired_letter_is_not_active(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:20:00', 'Asia/Pontianak')); try { $letter = OutgoingLetter::factory()->create(['status' => OutgoingLetterStatus::ISSUED, 'valid_from' => Carbon::parse('2026-08-25 10:20:00', 'Asia/Pontianak'), 'valid_until' => Carbon::parse('2026-09-01 10:20:00', 'Asia/Pontianak')]); $this->assertTrue($letter->isExpired()); $this->assertFalse($letter->isActive()); } finally { Carbon::setTestNow(); }
    }

    public function test_withdrawal_request_requires_issued_letter_and_pending_request_blocks_duplicate(): void
    {
        $user = User::factory()->superAdmin()->create(); $letter = OutgoingLetter::factory()->create(['status' => OutgoingLetterStatus::ISSUED]); $service = app(OutgoingLetterService::class); $request = $service->requestWithdrawal($letter, $user->id, 'Surat perlu ditarik.', 'withdrawals/statement.pdf'); $this->assertSame(OutgoingLetterWithdrawalStatus::PENDING, $request->status); $this->assertDatabaseHas('outgoing_letter_withdrawal_requests', ['id' => $request->id, 'outgoing_letter_id' => $letter->id, 'status' => OutgoingLetterWithdrawalStatus::PENDING->value]); $this->expectException(\DomainException::class); $service->requestWithdrawal($letter, $user->id, 'Pengajuan kedua.', 'withdrawals/second.pdf');
    }

    public function test_approved_withdrawal_changes_issued_letter_to_withdrawn(): void
    {
        $requester = User::factory()->superAdmin()->create(); $decider = User::factory()->superAdmin()->create(); $letter = OutgoingLetter::factory()->create(['status' => OutgoingLetterStatus::ISSUED]); $service = app(OutgoingLetterService::class); $request = $service->requestWithdrawal($letter, $requester->id, 'Surat dibatalkan.', 'withdrawals/statement.pdf'); $result = $service->approveWithdrawal($request, $decider->id, 'Disetujui.'); $letter->refresh(); $request->refresh(); $this->assertSame(OutgoingLetterStatus::WITHDRAWN, $result->status); $this->assertSame(OutgoingLetterStatus::WITHDRAWN, $letter->status); $this->assertSame(OutgoingLetterWithdrawalStatus::APPROVED, $request->status); $this->assertSame($decider->id, $request->decided_by); $this->assertNotNull($request->decided_at); $this->assertSame('Disetujui.', $request->decision_note);
    }

    public function test_rejected_withdrawal_keeps_letter_issued(): void
    {
        $requester = User::factory()->superAdmin()->create(); $decider = User::factory()->superAdmin()->create(); $letter = OutgoingLetter::factory()->create(['status' => OutgoingLetterStatus::ISSUED]); $service = app(OutgoingLetterService::class); $request = $service->requestWithdrawal($letter, $requester->id, 'Mohon ditarik.', 'withdrawals/statement.pdf'); $service->rejectWithdrawal($request, $decider->id, 'Alasan belum cukup.'); $letter->refresh(); $request->refresh(); $this->assertSame(OutgoingLetterStatus::ISSUED, $letter->status); $this->assertSame(OutgoingLetterWithdrawalStatus::REJECTED, $request->status); $this->assertSame($decider->id, $request->decided_by); $this->assertSame('Alasan belum cukup.', $request->decision_note);
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
