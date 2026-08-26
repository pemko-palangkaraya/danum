<?php

declare(strict_types=1);

namespace Tests\Feature\LetterTypes;

use App\Enums\UserRole;
use App\Models\LetterType;
use App\Models\LetterTypeVersion;
use App\Models\User;
use App\Services\LetterTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTypeVersionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_version_management(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'tenant_id' => null]);
        $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => 'active', 'body_template' => 'v1']);
        LetterTypeVersion::factory()->create([
            'letter_type_id' => $letterType->id,
            'version' => 1,
            'body_template' => 'v1',
            'effective_from' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->get(route('letter-types.versions', $letterType))
            ->assertOk();
    }

    public function test_tenant_user_cannot_open_version_management(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT_ADMIN]);
        $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('letter-types.versions', $letterType))
            ->assertForbidden();
    }

    public function test_creating_a_version_preserves_previous_version_and_closes_its_period(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'tenant_id' => null]);
        $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => 'active', 'body_template' => 'body-v1', 'template_path' => 'letter-templates/v1.docx']);
        $start = now()->subDay()->startOfSecond();
        $v1 = LetterTypeVersion::factory()->create([
            'letter_type_id' => $letterType->id,
            'version' => 1,
            'body_template' => 'body-v1',
            'template_path' => 'letter-templates/v1.docx',
            'effective_from' => $start,
        ]);

        $effectiveFrom = now()->addMinute()->startOfSecond();
        $v2 = app(LetterTypeService::class)->createVersion($letterType, [
            'body_template' => 'body-v2',
            'template_path' => 'letter-templates/v2.docx',
            'effective_from' => $effectiveFrom,
            'change_note' => 'Penyesuaian format surat.',
        ], $admin->id);

        $v1->refresh();
        $this->assertSame(2, $v2->version);
        $this->assertSame('body-v1', $v1->body_template);
        $this->assertSame('letter-templates/v1.docx', $v1->template_path);
        $this->assertTrue($v1->effective_until->equalTo($effectiveFrom));
        $this->assertSame('body-v2', $v2->body_template);
        $this->assertSame('letter-templates/v2.docx', $v2->template_path);
        $this->assertSame('Penyesuaian format surat.', $v2->change_note);
    }

    public function test_scheduled_future_version_does_not_replace_current_version(): void
    {
        $letterType = LetterType::factory()->create([
            'tenant_id' => null,
            'status' => 'active',
            'body_template' => 'body-v1',
            'template_path' => 'letter-templates/v1.docx',
        ]);
        $v1 = LetterTypeVersion::factory()->create([
            'letter_type_id' => $letterType->id,
            'version' => 1,
            'body_template' => 'body-v1',
            'template_path' => 'letter-templates/v1.docx',
            'effective_from' => now()->subDay(),
            'effective_until' => now()->addDay(),
        ]);
        $v2 = LetterTypeVersion::factory()->create([
            'letter_type_id' => $letterType->id,
            'version' => 2,
            'body_template' => 'body-v2',
            'template_path' => 'letter-templates/v2.docx',
            'effective_from' => now()->addDay(),
        ]);

        $resolved = app(LetterTypeService::class)->ensureCurrentVersion($letterType);

        $this->assertSame($v1->id, $resolved?->id);
        $this->assertSame(2, LetterTypeVersion::query()->where('letter_type_id', $letterType->id)->count());
        $this->assertSame('body-v2', $v2->fresh()->body_template);
    }

    public function test_version_creation_is_audited(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'tenant_id' => null]);
        $letterType = LetterType::factory()->create(['tenant_id' => null, 'body_template' => 'v1']);
        LetterTypeVersion::factory()->create([
            'letter_type_id' => $letterType->id,
            'version' => 1,
            'effective_from' => now()->subDay(),
        ]);

        $this->actingAs($admin);
        $version = app(LetterTypeService::class)->createVersion($letterType, [
            'body_template' => 'v2',
            'template_path' => 'letter-templates/v2.docx',
            'effective_from' => now()->addMinute(),
            'change_note' => 'Perubahan format.',
        ], $admin->id);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'letter_type.version.created',
            'auditable_id' => $version->id,
        ]);
    }

    public function test_version_start_cannot_go_backwards(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'tenant_id' => null]);
        $letterType = LetterType::factory()->create(['tenant_id' => null, 'body_template' => 'v1']);
        LetterTypeVersion::factory()->create([
            'letter_type_id' => $letterType->id,
            'version' => 1,
            'effective_from' => now()->subDay(),
        ]);

        $this->actingAs($admin);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('setelah versi terakhir');

        app(LetterTypeService::class)->createVersion($letterType, [
            'body_template' => 'v2',
            'effective_from' => now()->subDay(),
            'change_note' => 'Invalid.',
        ], $admin->id);
    }
}
