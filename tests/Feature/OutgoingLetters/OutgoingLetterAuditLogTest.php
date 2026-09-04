<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\AuditLog;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\User;
use App\Services\DocxPdfService;
use App\Services\OutgoingLetterService;
use App\Services\OutgoingLetterWorkflowService;
use App\Services\PdfSigningService;
use App\Services\SignerCertificateService;
use App\Services\SignerPinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class OutgoingLetterAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_update_submit_validate_and_issue_are_audited(): void
    {
        Storage::fake('local');

        $actor = User::factory()->superAdmin()->create();
        $validator = User::factory()->superAdmin()->create();
        app(SignerPinService::class)->set($actor, '123456');
        $letter = OutgoingLetter::factory()->create(['status' => OutgoingLetterStatus::DRAFT, 'validator_user_id' => $validator->id, 'signer_user_id' => $actor->id]);
        $position = Position::factory()->signatory()->create();
        $holder = PositionHolder::factory()->create(['position_id' => $position->id, 'tenant_id' => $letter->tenant_id, 'user_id' => $actor->id, 'started_at' => now()->subDay(), 'ended_at' => null]);
        $letter->update(['signer_position_id' => $position->id, 'signer_user_id' => $holder->user_id, 'generated_docx_path' => 'outgoing-letters/test.docx']);
        app(SignerCertificateService::class)->generate($position, $holder, $actor);

        $this->app->instance(DocxPdfService::class, Mockery::mock(DocxPdfService::class, function ($mock): void {
            $mock->shouldReceive('convert')->once()->andReturn('outgoing-letters/test.pdf');
        }));
        $this->app->instance(PdfSigningService::class, Mockery::mock(PdfSigningService::class, function ($mock): void {
            $mock->shouldReceive('sign')->once()->andReturn('outgoing-letters/signed/test-signed.pdf');
        }));

        $service = app(OutgoingLetterService::class);
        $workflow = app(OutgoingLetterWorkflowService::class);
        $this->actingAs($actor);
        $source = $letter->only(['tenant_id','created_by','letter_type_id','letter_type_version_id','validator_user_id','signer_user_id','number','recipient_name','recipient_address','subject','content','status']); $source['number'] = fake()->unique()->bothify('###/AUDIT/####');
        $created = $service->create($source, $actor->id); $created->update(['signer_position_id' => $position->id, 'signer_user_id' => $actor->id, 'generated_docx_path' => 'outgoing-letters/test.docx']); $service->update($created, ['subject' => 'Subjek Audit Baru']); $created->refresh(); $workflow->submit($created, $actor->id); $created->refresh(); $workflow->validate($created, $validator->id, 'Saya telah memeriksa isi dan kelengkapan surat.'); $created->refresh(); $service->issue($created, $actor->id, 'Saya menyetujui dan menandatangani surat untuk diterbitkan.', '123456');
        $this->assertSame(['outgoing_letter.created','outgoing_letter.updated','outgoing_letter.submitted','outgoing_letter.validated','outgoing_letter.signed','outgoing_letter.issued'], AuditLog::query()->where('auditable_type', OutgoingLetter::class)->where('auditable_id', $created->id)->orderBy('created_at')->pluck('action')->all());
        $created->refresh(); $this->assertSame('Saya telah memeriksa isi dan kelengkapan surat.', $created->verification_note); $this->assertSame('Saya menyetujui dan menandatangani surat untuk diterbitkan.', $created->signing_note); $this->assertSame('pades-b-b', $created->signature_profile); $this->assertSame('outgoing-letters/signed/test-signed.pdf', $created->signed_pdf_path);
    }

    public function test_cancel_is_audited(): void
    {
        $actor = User::factory()->superAdmin()->create(); $letter = OutgoingLetter::factory()->create(['status' => OutgoingLetterStatus::DRAFT]); app(OutgoingLetterWorkflowService::class)->cancel($letter, $actor->id);
        $this->assertSame(['outgoing_letter.cancelled'], AuditLog::query()->where('auditable_type', OutgoingLetter::class)->where('auditable_id', $letter->id)->orderBy('created_at')->pluck('action')->all());
    }

    public function test_rejection_and_withdrawal_decision_are_audited_without_exposing_verification_token(): void
    {
        $requester = User::factory()->superAdmin()->create(); $decider = User::factory()->superAdmin()->create(); $letter = OutgoingLetter::factory()->create(['status' => OutgoingLetterStatus::ISSUED]); $service = app(OutgoingLetterService::class); $request = $service->requestWithdrawal($letter, $requester->id, 'Surat perlu ditarik.', 'withdrawals/statement.pdf'); $service->approveWithdrawal($request, $decider->id, 'Disetujui.');
        $logs = AuditLog::query()->where('auditable_type', OutgoingLetter::class)->where('auditable_id', $letter->id)->orderBy('created_at')->get(); $this->assertSame(['outgoing_letter.withdrawal_requested','outgoing_letter.withdrawn'], $logs->pluck('action')->all()); $this->assertArrayNotHasKey('verification_token', $logs->last()->new_values ?? []); $this->assertSame('withdrawn', $logs->last()->new_values['status']);
    }
}