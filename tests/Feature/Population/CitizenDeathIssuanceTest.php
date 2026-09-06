<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\SignerCertificate;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DocxPdfService;
use App\Services\DocxTteService;
use App\Services\OutgoingLetterService;
use App\Services\PdfSigningService;
use App\Services\SignerPinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CitizenDeathIssuanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->mock(DocxTteService::class, function ($mock): void {
            $mock->shouldReceive('createIssuedCopy')
                ->andReturn('outgoing-letters/test/issued.docx');
        });
        $this->mock(DocxPdfService::class, function ($mock): void {
            $mock->shouldReceive('convert')
                ->andReturn('outgoing-letters/test/unsigned.pdf');
        });
        $this->mock(PdfSigningService::class, function ($mock): void {
            $mock->shouldReceive('sign')
                ->andReturn('outgoing-letters/test/signed.pdf');
        });
    }

    public function test_issuing_death_letter_updates_citizen_and_removes_active_family_membership(): void
    {
        $tenant = Tenant::factory()->create();
        $signer = User::factory()->tenantAdmin($tenant)->create();
        $citizen = Citizen::factory()->forTenant($tenant)->create([
            'tanggal_lahir' => '1980-01-10',
            'status_kependudukan' => 'aktif',
            'tanggal_meninggal' => null,
        ]);
        $family = Family::factory()->forTenant($tenant)->create([
            'head_citizen_id' => $citizen->id,
        ]);
        $membership = FamilyMember::factory()
            ->forFamily($family)
            ->forCitizen($citizen)
            ->relation('head')
            ->create();
        $type = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'sk_kematian',
            'status' => LetterTypeStatus::ACTIVE,
        ]);
        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $type->id,
            'citizen_id' => $citizen->id,
            'status' => OutgoingLetterStatus::VALIDATED,
            'signer_user_id' => $signer->id,
            'input_data' => ['tanggal_meninggal' => '12 Des 2025'],
        ]);

        $this->prepareSignerCredentials($letter, $signer);

        app(OutgoingLetterService::class)->issue(
            $letter->fresh(),
            $signer->id,
            'Saya menyetujui dan menandatangani surat ini.',
            '123456',
        );

        $citizen->refresh();
        $membership->refresh();

        $this->assertSame('meninggal', $citizen->status_kependudukan);
        $this->assertSame('2025-12-12', $citizen->tanggal_meninggal->toDateString());
        $this->assertSame('inactive', $membership->status);
        $this->assertSame('2025-12-12', $membership->tanggal_selesai->toDateString());
        $this->assertDatabaseHas('population_events', [
            'tenant_id' => $tenant->id,
            'citizen_id' => $citizen->id,
            'event_type' => 'death',
            'event_date' => '2025-12-12',
            'effective_date' => '2025-12-12',
            'document_number' => $letter->number,
            'created_by' => $letter->created_by,
        ]);
    }

    private function prepareSignerCredentials(OutgoingLetter $letter, User $signer): void
    {
        $position = Position::factory()->signatory()->create();
        PositionHolder::factory()->create([
            'position_id' => $position->id,
            'tenant_id' => $letter->tenant_id,
            'user_id' => $signer->id,
            'started_at' => now()->subDay(),
            'ended_at' => null,
        ]);

        $letter->forceFill([
            'signer_position_id' => $position->id,
            'generated_docx_path' => 'outgoing-letters/test/source.docx',
        ])->save();
        Storage::disk('local')->put('outgoing-letters/test/source.docx', 'test docx content');

        app(SignerPinService::class)->set($signer, '123456');

        $certificate = SignerCertificate::query()->create([
            'position_id' => $position->id,
            'user_id' => $signer->id,
            'type' => 'self_signed',
            'serial_number' => 'TEST-' . strtoupper(bin2hex(random_bytes(8))),
            'fingerprint_sha256' => hash('sha256', $signer->id . '-' . $position->id . '-' . microtime(true)),
            'certificate_pem' => 'TEST CERTIFICATE',
            'private_key_encrypted' => 'TEST PRIVATE KEY',
            'valid_from' => now()->subMinute(),
            'valid_until' => now()->addYear(),
            'revoked_at' => null,
            'is_active' => true,
            'generated_by' => $signer->id,
        ]);

        $this->assertTrue($certificate->fresh()->isUsable());
        $letter->forceFill(['signature_certificate_id' => $certificate->id])->save();
    }
}
