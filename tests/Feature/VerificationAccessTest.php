<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Enums\VerificationAccessLevel;
use App\Models\LetterClassification;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_document_can_be_verified_and_downloaded_without_login(): void
    {
        Storage::fake('local');
        $letter = $this->createIssuedLetter(VerificationAccessLevel::PUBLIC);
        Storage::disk('local')->put($letter->unsigned_pdf_path, '%PDF-public');
        $letter->update(['unsigned_pdf_path' => $letter->unsigned_pdf_path]);
        $letter->refresh();

        $this->get(route('verification.show', $letter->verification_token))
            ->assertOk()
            ->assertSee('Dokumen Terverifikasi')
            ->assertSee($letter->number)
            ->assertSee($letter->document_hash);

        $this->get(route('verification.document', $letter->verification_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="'.$this->safeFilename($letter->number).'"');

        $this->assertSame(hash('sha256', '%PDF-public'), $letter->document_hash);
        $this->assertSame('SHA-256', $letter->document_hash_algorithm);

        $this->assertDatabaseHas('verification_logs', [
            'document_id' => $letter->id,
            'action' => 'VERIFY',
            'result' => 'SUCCESS',
            'user_type' => 'public',
        ]);

        $this->assertDatabaseHas('verification_logs', [
            'document_id' => $letter->id,
            'action' => 'DOWNLOAD',
            'result' => 'SUCCESS',
            'user_type' => 'public',
        ]);
    }

    public function test_protected_document_requires_login_before_download(): void
    {
        Storage::fake('local');
        $letter = $this->createIssuedLetter(VerificationAccessLevel::PROTECTED);
        Storage::disk('local')->put($letter->unsigned_pdf_path, '%PDF-protected');

        $this->get(route('verification.show', $letter->verification_token))
            ->assertOk()
            ->assertSee('Tanda Tangan Elektronik Tercatat')
            ->assertSee('Login untuk mengakses');

        $this->get(route('verification.document', $letter->verification_token))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('verification_logs', [
            'document_id' => $letter->id,
            'action' => 'DOWNLOAD',
            'result' => 'UNAUTHENTICATED',
            'user_type' => 'public',
        ]);
    }

    public function test_protected_document_can_be_downloaded_by_authorized_tenant_user(): void
    {
        Storage::fake('local');
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        $letter = $this->createIssuedLetter(VerificationAccessLevel::PROTECTED, $tenant);
        Storage::disk('local')->put($letter->unsigned_pdf_path, '%PDF-protected');

        $this->actingAs($user)
            ->get(route('verification.document', $letter->verification_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('verification_logs', [
            'document_id' => $letter->id,
            'action' => 'DOWNLOAD',
            'result' => 'SUCCESS',
            'user_id' => $user->id,
            'user_type' => 'authenticated',
        ]);
    }

    public function test_protected_document_for_wrong_tenant_is_forbidden_and_logged(): void
    {
        Storage::fake('local');
        $letterTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($otherTenant)->create();
        $letter = $this->createIssuedLetter(VerificationAccessLevel::PROTECTED, $letterTenant);
        Storage::disk('local')->put($letter->unsigned_pdf_path, '%PDF-protected');

        $this->actingAs($user)
            ->get(route('verification.document', $letter->verification_token))
            ->assertForbidden();

        $this->assertDatabaseHas('verification_logs', [
            'document_id' => $letter->id,
            'action' => 'DOWNLOAD',
            'result' => 'FORBIDDEN',
            'user_id' => $user->id,
        ]);
    }

    public function test_restricted_document_can_be_verified_but_never_downloaded(): void
    {
        Storage::fake('local');
        $letter = $this->createIssuedLetter(VerificationAccessLevel::RESTRICTED);
        Storage::disk('local')->put($letter->unsigned_pdf_path, '%PDF-restricted');

        $this->get(route('verification.show', $letter->verification_token))
            ->assertOk()
            ->assertSee('Dokumen Terdaftar')
            ->assertDontSee('Lihat Dokumen');

        $this->get(route('verification.document', $letter->verification_token))
            ->assertForbidden();

        $this->assertDatabaseHas('verification_logs', [
            'document_id' => $letter->id,
            'action' => 'DOWNLOAD',
            'result' => 'FORBIDDEN',
        ]);
    }

    public function test_invalid_verification_token_returns_not_found_and_is_logged(): void
    {
        $this->get(route('verification.json', 'missing-token'))
            ->assertNotFound()
            ->assertJson(['verified' => false]);

        $this->assertDatabaseHas('verification_logs', [
            'action' => 'VERIFY',
            'result' => 'NOT_FOUND',
            'user_type' => 'public',
        ]);
    }

    private function createIssuedLetter(VerificationAccessLevel $accessLevel, ?Tenant $tenant = null): OutgoingLetter
    {
        $tenant ??= Tenant::factory()->create();
        $classification = LetterClassification::factory()->create([
            'verification_access_level' => $accessLevel,
        ]);
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_classification_id' => $classification->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);

        return OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'status' => OutgoingLetterStatus::ISSUED,
            'issued_at' => now(),
            'unsigned_pdf_path' => 'letters/test-'.$accessLevel->value.'.pdf',
        ]);
    }

    private function safeFilename(string $number): string
    {
        $safeNumber = trim(str_replace(['/', '\\'], '-', $number));
        $safeNumber = trim($safeNumber, '.-');

        return ($safeNumber !== '' ? $safeNumber : 'dokumen') . '.pdf';
    }
}
