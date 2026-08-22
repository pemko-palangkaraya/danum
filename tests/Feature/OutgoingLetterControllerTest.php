<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LetterTypeStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterControllerTest extends TestCase
{
    use RefreshDatabase;

    // Existing test methods remain unchanged above.

    public function test_tenant_user_cannot_download_outgoing_letter_without_generated_docx(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'generated_docx_path' => null,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this->actingAs($user)
            ->get("/api/outgoing-letters/{$letter->id}/pdf")
            ->assertStatus(422);
    }
}
