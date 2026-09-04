<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Enums\UserRole;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OutgoingLetterService;
use App\Services\OutgoingLetterWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    private function letter(OutgoingLetterStatus $status = OutgoingLetterStatus::DRAFT): array
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::TENANT_ADMIN]);
        $validator = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::TENANT_USER]);
        $type = LetterType::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);
        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $type->id,
            'status' => $status,
            'validator_user_id' => $validator->id,
            'submitted_at' => $status === OutgoingLetterStatus::DRAFT ? now() : null,
        ]);
        return [$letter, $admin, $validator];
    }

    public function test_draft_can_be_submitted_only_when_validator_exists(): void
    {
        [$letter, $admin] = $this->letter();
        $letter->update(['validator_user_id' => null, 'submitted_at' => null]);
        $this->expectException(\DomainException::class);
        app(OutgoingLetterWorkflowService::class)->submit($letter, $admin->id);
    }

    public function test_validate_cannot_be_called_on_issued_letter(): void
    {
        [$letter, $admin] = $this->letter(OutgoingLetterStatus::ISSUED);
        $this->expectException(\DomainException::class);
        app(OutgoingLetterWorkflowService::class)->validate($letter, $admin->id);
    }

    public function test_issue_cannot_skip_validation(): void
    {
        [$letter, $admin] = $this->letter(OutgoingLetterStatus::DRAFT);
        $this->expectException(\DomainException::class);
        app(OutgoingLetterService::class)->issue($letter, $admin->id);
    }

    public function test_issued_letter_cannot_be_cancelled_or_modified(): void
    {
        [$letter, $admin] = $this->letter(OutgoingLetterStatus::ISSUED);
        $workflow = app(OutgoingLetterWorkflowService::class);
        $service = app(OutgoingLetterService::class);

        try { $workflow->cancel($letter, $admin->id); $this->fail('Expected cancel to fail.'); } catch (\DomainException) {}
        try { $service->update($letter, ['subject' => 'changed']); $this->fail('Expected update to fail.'); } catch (\DomainException) {}

        $this->assertSame(OutgoingLetterStatus::ISSUED, $letter->refresh()->status);
    }

    public function test_withdrawn_letter_cannot_be_modified_or_withdrawn_again(): void
    {
        [$letter, $admin] = $this->letter(OutgoingLetterStatus::WITHDRAWN);
        $service = app(OutgoingLetterService::class);

        try { $service->update($letter, ['subject' => 'changed']); $this->fail('Expected update to fail.'); } catch (\DomainException) {}
        $this->expectException(\DomainException::class);
        $service->requestWithdrawal($letter, $admin->id, 'duplicate', 'statement.pdf');
    }
}