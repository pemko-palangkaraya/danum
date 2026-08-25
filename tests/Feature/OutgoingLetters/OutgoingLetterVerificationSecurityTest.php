<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterVerificationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function letter(OutgoingLetterStatus $status): OutgoingLetter
    {
        $tenant=Tenant::factory()->create();
        $type=LetterType::factory()->create(['tenant_id'=>$tenant->id,'status'=>'active']);
        return OutgoingLetter::factory()->create([
            'tenant_id'=>$tenant->id,
            'letter_type_id'=>$type->id,
            'status'=>$status,
            'verification_token'=>'verify-'.str()->uuid(),
            'issued_at'=>now()->toDateString(),
            'valid_from'=>now()->subHour(),
            'valid_until'=>now()->addMonth(),
        ]);
    }

    public function test_unknown_verification_token_returns_not_found(): void
    {
        $this->get('/verify/non-existent-token')->assertNotFound();
    }

    public function test_withdrawn_document_remains_verifiable_as_withdrawn(): void
    {
        $letter=$this->letter(OutgoingLetterStatus::WITHDRAWN);
        $this->get('/verify/'.$letter->verification_token)
            ->assertOk()
            ->assertSee('Ditandatangani');
    }

    public function test_expired_document_remains_verifiable_as_expired(): void
    {
        $letter=$this->letter(OutgoingLetterStatus::ISSUED);
        $letter->update(['valid_from'=>now()->subMonths(2),'valid_until'=>now()->subDay()]);
        $this->get('/verify/'.$letter->verification_token)
            ->assertOk()
            ->assertSee('Kadaluarsa');
    }
}
