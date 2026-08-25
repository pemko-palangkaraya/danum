<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    // Existing tests remain unchanged.

    public function test_public_verification_hides_unissued_or_unknown_documents(): void
    {
        $draft = OutgoingLetter::factory()->create([
            'status' => OutgoingLetterStatus::DRAFT,
            'verification_token' => Str::random(64),
        ]);

        $this->get('/verify/'.$draft->verification_token)
            ->assertNotFound();

        $this->get('/verify/unknown-token-for-danum')
            ->assertNotFound();
    }
}
