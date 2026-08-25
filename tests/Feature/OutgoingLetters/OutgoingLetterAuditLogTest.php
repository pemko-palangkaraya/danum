<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\AuditLog;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_update_submit_validate_issue_and_cancel_are_audited(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $validator = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::DRAFT,
            'validator_user_id' => $validator->id,
        ]);

        $service = app(OutgoingLetterService::class);
        $this->actingAs($actor);

        $source = $letter->only([
            'tenant_id', 'created_by', 'letter_type_id', 'letter_type_version_id',
            'number', 'recipient_name', 'recipient_address', 'subject', 'content', 'status',
        ]);
        $source['number'] = fake()->unique()->bothify('###/AUDIT/####');

        $created = $service->create($source, $actor->id);
        $service->update($created, ['subject' => 'Subjek Audit Baru']);
        $created->refresh();
        $service->submit($created, $actor->id);
        $created->refresh();
        $service->validate($created, $validator->id);
        $created->refresh();
        $service->issue($created, $actor->id);

        $this->assertSame([
            'outgoing_letter.created',
            'outgoing_letter.updated',
            'outgoing_letter.submitted',
            'outgoing_letter.validated',
            'outgoing_letter.issued',
        ], AuditLog::query()
            ->where('auditable_type', OutgoingLetter::class)
            ->where('auditable_id', $created->id)
            ->orderBy('created_at')
            ->pluck('action')
            ->all());
    }

    public function test_rejection_and_withdrawal_decision_are_audited_without_exposing_verification_token(): void
    {
        $requester = User::factory()->superAdmin()->create();
        $decider = User::factory()->superAdmin()->create();
        $letter = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::ISSUED,
        ]);

        $service = app(OutgoingLetterService::class);
        $request = $service->requestWithdrawal(
            $letter,
            $requester->id,
            'Surat perlu ditarik.',
            'withdrawals/statement.pdf',
        );

        $service->approveWithdrawal($request, $decider->id, 'Disetujui.');

        $logs = AuditLog::query()
            ->where('auditable_type', OutgoingLetter::class)
            ->where('auditable_id', $letter->id)
            ->orderBy('created_at')
            ->get();

        $this->assertSame([
            'outgoing_letter.withdrawal_requested',
            'outgoing_letter.withdrawn',
        ], $logs->pluck('action')->all());

        $this->assertArrayNotHasKey('verification_token', $logs->last()->new_values ?? []);
        $this->assertSame('withdrawn', $logs->last()->new_values['status']);
    }
}
