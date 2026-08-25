<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Enums\UserRole;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterWithdrawalIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $tenant=Tenant::factory()->create();
        $admin=User::factory()->create(['tenant_id'=>$tenant->id,'role'=>UserRole::TENANT_ADMIN]);
        $super=User::factory()->create(['tenant_id'=>null,'role'=>UserRole::SUPER_ADMIN]);
        $type=LetterType::factory()->create(['tenant_id'=>$tenant->id,'status'=>'active']);
        $letter=OutgoingLetter::factory()->create(['tenant_id'=>$tenant->id,'letter_type_id'=>$type->id,'status'=>OutgoingLetterStatus::ISSUED]);
        return [$letter,$admin,$super];
    }

    public function test_only_one_pending_withdrawal_request_is_allowed(): void
    {
        [$letter,$admin]= $this->fixture();
        $service=app(OutgoingLetterService::class);
        $service->requestWithdrawal($letter,$admin->id,'first','first.pdf');
        $this->expectException(\DomainException::class);
        $service->requestWithdrawal($letter,$admin->id,'second','second.pdf');
    }

    public function test_rejected_request_can_be_followed_by_a_new_request(): void
    {
        [$letter,$admin,$super]= $this->fixture();
        $service=app(OutgoingLetterService::class);
        $first=$service->requestWithdrawal($letter,$admin->id,'first','first.pdf');
        $service->rejectWithdrawal($first,$super->id,'Not approved');
        $second=$service->requestWithdrawal($letter,$admin->id,'second','second.pdf');
        $this->assertSame(OutgoingLetterWithdrawalStatus::PENDING,$second->status);
    }

    public function test_approved_request_cannot_be_decided_again(): void
    {
        [$letter,$admin,$super]= $this->fixture();
        $service=app(OutgoingLetterService::class);
        $request=$service->requestWithdrawal($letter,$admin->id,'reason','statement.pdf');
        $service->approveWithdrawal($request,$super->id,'Approved');
        $this->expectException(\DomainException::class);
        $service->approveWithdrawal($request->refresh(),$super->id,'Again');
    }

    public function test_rejection_requires_decision_note(): void
    {
        [$letter,$admin,$super]= $this->fixture();
        $service=app(OutgoingLetterService::class);
        $request=$service->requestWithdrawal($letter,$admin->id,'reason','statement.pdf');
        $this->expectException(\DomainException::class);
        $service->rejectWithdrawal($request,$super->id,'');
    }
}
