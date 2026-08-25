<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Enums\UserRole;
use App\Models\LetterType;
use App\Models\LetterTypePermission;
use App\Models\LetterTypeVersion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterTemplateVersionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function createLetterTypeFor(Tenant $tenant): LetterType
    {
        $type = LetterType::factory()->create(['tenant_id' => null, 'status' => 'active', 'body_template' => 'v1']);
        LetterTypePermission::query()->create(['tenant_id' => $tenant->id, 'letter_type_id' => $type->id, 'allowed' => true]);
        return $type;
    }

    private function letterData(Tenant $tenant, LetterType $type, string $number): array
    {
        return [
            'tenant_id' => $tenant->id,
            'letter_type_id' => $type->id,
            'number' => $number,
            'recipient_name' => 'Budi',
            'recipient_address' => 'Jl. Merdeka',
            'subject' => 'Test',
            'content' => 'Test',
            'status' => OutgoingLetterStatus::DRAFT->value,
        ];
    }

    public function test_new_letter_locks_the_active_template_version(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::TENANT_ADMIN]);
        $type = $this->createLetterTypeFor($tenant);
        $version = LetterTypeVersion::query()->create(['letter_type_id' => $type->id, 'version' => 1, 'body_template' => 'v1', 'is_active' => true, 'effective_from' => now()->subDay()]);

        $letter = app(OutgoingLetterService::class)->create($this->letterData($tenant, $type, '001/SK/2026'), $user->id);

        $this->assertSame($version->id, $letter->letter_type_version_id);
    }

    public function test_changing_letter_type_template_does_not_change_existing_letter_version(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::TENANT_ADMIN]);
        $type = $this->createLetterTypeFor($tenant);
        $version = LetterTypeVersion::query()->create(['letter_type_id' => $type->id, 'version' => 1, 'body_template' => 'v1', 'is_active' => true, 'effective_from' => now()->subDay()]);

        $letter = app(OutgoingLetterService::class)->create($this->letterData($tenant, $type, '002/SK/2026'), $user->id);

        $type->update(['body_template' => 'v2']);
        LetterTypeVersion::query()->create(['letter_type_id' => $type->id, 'version' => 2, 'body_template' => 'v2', 'is_active' => true, 'effective_from' => now()]);

        $this->assertSame($version->id, $letter->refresh()->letter_type_version_id);
    }
}
