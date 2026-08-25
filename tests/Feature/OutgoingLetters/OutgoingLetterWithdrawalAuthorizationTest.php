<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Enums\UserRole;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterWithdrawalRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterWithdrawalAuthorizationTest extends TestCase
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

    public function test_statement_path_is_required(): void
    {
        [$letter,$admin]= $this->fixture();
        $this->expectException(\DomainException::class);
        app(OutgoingLetterService::class)->requestWithdrawal($letter,$admin->id,'Alasan','');
    }

    public function test_tenant_admin_cannot_approve_withdrawal(): void
    {
        [$letter,$admin,$super]=$this->fixture();
        $request=app(OutgoingLetterService::class)->requestWithdrawal($letter,$admin->id,'Alasan','statement.pdf');
        $this->expectException(\DomainException::class);
        app(OutgoingLetterService::class)->approveWithdrawal($request,$admin->id);
    }

    public function test_super_admin_can_approve_withdrawal(): void
    {
        [$letter,$admin,$super]=$this->fixture();
        $request=app(OutgoingLetterService::class)->requestWithdrawal($letter,$admin->id,'Alasan','statement.pdf');
        $result=app(OutgoingLetterService::class)->approveWithdrawal($request,$super->id,'Approved');
        $this->assertSame(OutgoingLetterStatus::WITHDRAWN,$result->status);
        $this->assertSame(OutgoingLetterWithdrawalStatus::APPROVED,$request->refresh()->status);
        $this->assertSame($super->id,$request->decided_by);
        $this->assertNotNull($request->decided_at);
    }

    public function test_super_admin_rejection_keeps_letter_issued(): void
    {
        [$letter,$admin,$super]=$this->fixture();
        $request=app(OutgoingLetterService::class)->requestWithdrawal($letter,$admin->id,'Alasan','statement.pdf');
        $request=app(OutgoingLetterService::class)->rejectWithdrawal($request,$super->id,'Tidak memenuhi syarat');
        $this->assertSame(OutgoingLetterWithdrawalStatus::REJECTED,$request->status);
        $this->assertSame(OutgoingLetterStatus::ISSUED,$letter->refresh()->status);
        $this->assertSame($super->id,$request->decided_by);
        $this->assertNotNull($request->decided_at);
    }
}
