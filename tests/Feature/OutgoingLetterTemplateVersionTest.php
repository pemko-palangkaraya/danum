<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LetterTypeStatus;
use App\Models\LetterType;
use App\Models\LetterTypeVersion;
use App\Models\OutgoingLetter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterTemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_outgoing_letter_keeps_the_template_version_used_to_generate_it(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Kelurahan Danum']);
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
            'body_template' => 'Versi pertama: {{number}} - {{recipient_name}}.',
        ]);
        $user = User::factory()->tenantUser($tenant)->create();

        $first = $this->actingAs($user)
            ->postJson('/api/outgoing-letters', [
                'letter_type_id' => $letterType->id,
                'number' => '001/SK/2026',
                'recipient_name' => 'Budi Santoso',
                'recipient_address' => 'Palangka Raya',
                'subject' => 'Surat Keterangan',
            ])
            ->assertCreated();

        $firstVersionId = $first->json('data.letter_type_version_id');

        $letterType->update([
            'body_template' => 'Versi kedua: {{number}} - {{recipient_name}} - {{subject}}.',
        ]);

        $secondVersion = LetterTypeVersion::query()
            ->where('letter_type_id', $letterType->id)
            ->orderByDesc('version')
            ->first();

        $this->assertNotNull($secondVersion);
        $this->assertNotSame($firstVersionId, $secondVersion->id);

        $second = $this->actingAs($user)
            ->postJson('/api/outgoing-letters', [
                'letter_type_id' => $letterType->id,
                'number' => '002/SK/2026',
                'recipient_name' => 'Siti Aminah',
                'recipient_address' => 'Palangka Raya',
                'subject' => 'Surat Keterangan Baru',
            ])
            ->assertCreated();

        $this->assertSame($firstVersionId, $first->json('data.letter_type_version_id'));
        $this->assertSame($secondVersion->id, $second->json('data.letter_type_version_id'));
        $this->assertSame(
            'Versi pertama: 001/SK/2026 - Budi Santoso.',
            $first->json('data.content'),
        );
        $this->assertSame(
            'Versi kedua: 002/SK/2026 - Siti Aminah - Surat Keterangan Baru.',
            $second->json('data.content'),
        );

        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $first->json('data.id'),
            'letter_type_version_id' => $firstVersionId,
        ]);
    }
}
