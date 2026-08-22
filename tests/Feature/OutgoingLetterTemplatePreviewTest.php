<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LetterTypeStatus;
use App\Models\LetterType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterTemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_preview_an_active_letter_template(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Pemerintah Kota Palangka Raya',
            'city' => 'Palangka Raya',
        ]);
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
            'body_template' => 'Nomor {{number}} untuk {{recipient_name}} di {{tenant_city}}.',
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this->actingAs($user)
            ->postJson('/api/outgoing-letters/preview', [
                'letter_type_id' => $letterType->id,
                'number' => '001/SK/2026',
                'recipient_name' => 'Budi Santoso',
                'recipient_address' => 'Palangka Raya',
                'subject' => 'Surat Keterangan',
            ])
            ->assertOk()
            ->assertJsonPath(
                'data.content',
                'Nomor 001/SK/2026 untuk Budi Santoso di Palangka Raya.',
            );
    }

    public function test_preview_rejects_a_letter_type_from_another_tenant(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $otherTenant->id,
            'status' => LetterTypeStatus::ACTIVE,
            'body_template' => 'Nomor {{number}}.',
        ]);
        $user = User::factory()->tenantUser($ownTenant)->create();

        $this->actingAs($user)
            ->postJson('/api/outgoing-letters/preview', [
                'letter_type_id' => $letterType->id,
                'number' => '001/SK/2026',
                'recipient_name' => 'Budi Santoso',
                'recipient_address' => 'Palangka Raya',
                'subject' => 'Surat Keterangan',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['letter_type_id']);
    }

    public function test_preview_requires_a_template(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
            'body_template' => null,
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $this->actingAs($user)
            ->postJson('/api/outgoing-letters/preview', [
                'letter_type_id' => $letterType->id,
                'number' => '001/SK/2026',
                'recipient_name' => 'Budi Santoso',
                'recipient_address' => 'Palangka Raya',
                'subject' => 'Surat Keterangan',
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'The selected letter type has no template.',
            );
    }
}
