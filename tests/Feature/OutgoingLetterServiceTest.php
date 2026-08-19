<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterStatusHistory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\LetterType;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use App\Repositories\Contracts\OutgoingLetterStatusHistoryRepositoryInterface;
use App\Services\OutgoingLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_find_outgoing_letter(): void
    {
        $tenant = Tenant::factory()->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $service = app(OutgoingLetterService::class);

        $result = $service->find(
            $letter->id,
            $tenant->id,
        );

        $this->assertNotNull($result);
        $this->assertSame($letter->id, $result->id);
    }

    public function test_can_get_all_outgoing_letters_for_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        OutgoingLetter::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
        ]);

        OutgoingLetter::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $service = app(OutgoingLetterService::class);

        $result = $service->getAll($tenant->id);

        $this->assertCount(2, $result);
        $this->assertTrue(
            $result->every(
                fn(OutgoingLetter $letter): bool =>
                $letter->tenant_id === $tenant->id,
            ),
        );
    }

    public function test_can_create_outgoing_letter_through_repository(): void
    {
        $tenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()->tenantUser($tenant)->create();

        $service = app(OutgoingLetterService::class);

        $letter = $service->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'number' => '001/SK/2026',
            'recipient_name' => 'Budi Santoso',
            'recipient_address' => 'Palangka Raya',
            'subject' => 'Surat Keterangan',
            'content' => 'Isi surat.',
            'status' => OutgoingLetterStatus::DRAFT,
        ], $user->id);

        $this->assertInstanceOf(
            OutgoingLetter::class,
            $letter,
        );

        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $letter->id,
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'status' => OutgoingLetterStatus::DRAFT->value,
        ]);

        $this->assertDatabaseHas('outgoing_letter_status_histories', [
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::DRAFT->value,
            'action' => 'created',
        ]);
    }

    public function test_can_update_outgoing_letter_through_repository(): void
    {
        $tenant = Tenant::factory()->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $service = app(OutgoingLetterService::class);

        $result = $service->update($letter, [
            'subject' => 'Perihal Diperbarui',
        ]);

        $this->assertSame(
            'Perihal Diperbarui',
            $result->subject,
        );

        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $letter->id,
            'subject' => 'Perihal Diperbarui',
        ]);
    }

    public function test_can_delete_outgoing_letter(): void
    {
        $tenant = Tenant::factory()->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $service = app(OutgoingLetterService::class);

        $result = $service->delete($letter);

        $this->assertTrue($result);

        $this->assertSoftDeleted('outgoing_letters', [
            'id' => $letter->id,
        ]);
    }

    public function test_can_restore_outgoing_letter(): void
    {
        $tenant = Tenant::factory()->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $letter->delete();

        $service = app(OutgoingLetterService::class);

        $result = $service->restore($letter);

        $this->assertTrue($result);

        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $letter->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_validate_draft_letter(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OutgoingLetterStatus::DRAFT,
        ]);

        $service = app(OutgoingLetterService::class);

        $result = $service->validate(
            $letter,
            $user->id,
        );

        $this->assertSame(
            OutgoingLetterStatus::VALIDATED,
            $result->status,
        );

        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $letter->id,
            'status' => OutgoingLetterStatus::VALIDATED->value,
        ]);

        $this->assertDatabaseHas('outgoing_letter_status_histories', [
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::VALIDATED->value,
            'action' => 'validated',
        ]);
    }

    public function test_can_issue_validated_letter(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OutgoingLetterStatus::VALIDATED,
        ]);

        $service = app(OutgoingLetterService::class);

        $result = $service->issue(
            $letter,
            $user->id,
        );

        $this->assertSame(
            OutgoingLetterStatus::ISSUED,
            $result->status,
        );

        $this->assertNotNull($result->issued_at);

        $this->assertDatabaseHas('outgoing_letters', [
            'id' => $letter->id,
            'status' => OutgoingLetterStatus::ISSUED->value,
        ]);

        $this->assertDatabaseHas('outgoing_letter_status_histories', [
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::ISSUED->value,
            'action' => 'issued',
        ]);
    }

    public function test_cannot_issue_draft_letter(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OutgoingLetterStatus::DRAFT,
        ]);

        $service = app(OutgoingLetterService::class);

        $this->expectException(\DomainException::class);

        $service->issue(
            $letter,
            $user->id,
        );
    }

    public function test_cannot_validate_non_draft_letter(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OutgoingLetterStatus::VALIDATED,
        ]);

        $service = app(OutgoingLetterService::class);

        $this->expectException(\DomainException::class);

        $service->validate(
            $letter,
            $user->id,
        );
    }

    public function test_can_cancel_draft_letter(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OutgoingLetterStatus::DRAFT,
        ]);

        $service = app(OutgoingLetterService::class);

        $result = $service->cancel(
            $letter,
            $user->id,
        );

        $this->assertSame(
            OutgoingLetterStatus::CANCELLED,
            $result->status,
        );

        $this->assertDatabaseHas('outgoing_letter_status_histories', [
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::CANCELLED->value,
            'action' => 'cancelled',
        ]);
    }

    public function test_can_cancel_validated_letter(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OutgoingLetterStatus::VALIDATED,
        ]);

        $service = app(OutgoingLetterService::class);

        $result = $service->cancel(
            $letter,
            $user->id,
        );

        $this->assertSame(
            OutgoingLetterStatus::CANCELLED,
            $result->status,
        );

        $this->assertDatabaseHas('outgoing_letter_status_histories', [
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::CANCELLED->value,
            'action' => 'cancelled',
        ]);
    }

    public function test_cannot_cancel_issued_letter(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OutgoingLetterStatus::ISSUED,
        ]);

        $service = app(OutgoingLetterService::class);

        $this->expectException(\DomainException::class);

        $service->cancel(
            $letter,
            $user->id,
        );
    }

    public function test_service_resolves_from_container(): void
    {
        $service = app(OutgoingLetterService::class);

        $this->assertInstanceOf(
            OutgoingLetterService::class,
            $service,
        );
    }

    public function test_service_uses_repository_for_persistence(): void
    {
        $repository = $this->createMock(
            OutgoingLetterRepositoryInterface::class,
        );

        $historyRepository = $this->createMock(
            OutgoingLetterStatusHistoryRepositoryInterface::class,
        );

        $this->app->instance(
            OutgoingLetterRepositoryInterface::class,
            $repository,
        );

        $this->app->instance(
            OutgoingLetterStatusHistoryRepositoryInterface::class,
            $historyRepository,
        );

        $tenant = Tenant::factory()->create();

        $letterType = \App\Models\LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()->tenantUser($tenant)->create();

        $letter = new OutgoingLetter([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'number' => '001/SK/2026',
            'recipient_name' => 'Budi Santoso',
            'recipient_address' => 'Palangka Raya',
            'subject' => 'Surat Keterangan',
            'content' => 'Isi surat.',
            'status' => OutgoingLetterStatus::DRAFT,
        ]);

        $letter->id = (string) \Illuminate\Support\Str::uuid();

        $repository
            ->expects($this->once())
            ->method('create')
            ->willReturn($letter);

        $historyRepository
            ->expects($this->once())
            ->method('create')
            ->with([
                'outgoing_letter_id' => $letter->id,
                'changed_by' => $user->id,
                'status' => OutgoingLetterStatus::DRAFT,
                'action' => 'created',
            ])
            ->willReturn(
                new OutgoingLetterStatusHistory([
                    'outgoing_letter_id' => $letter->id,
                    'changed_by' => $user->id,
                    'status' => OutgoingLetterStatus::DRAFT,
                    'action' => 'created',
                ]),
            );

        $service = app(OutgoingLetterService::class);

        $result = $service->create([
            'tenant_id' => $tenant->id,
            'letter_type_id' => $letterType->id,
            'number' => '001/SK/2026',
            'recipient_name' => 'Budi Santoso',
            'recipient_address' => 'Palangka Raya',
            'subject' => 'Surat Keterangan',
            'content' => 'Isi surat.',
            'status' => OutgoingLetterStatus::DRAFT,
        ], $user->id);

        $this->assertSame(
            $letter->id,
            $result->id,
        );
    }
}
