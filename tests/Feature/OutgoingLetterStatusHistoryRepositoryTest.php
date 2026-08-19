<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterStatusHistory;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\OutgoingLetterStatusHistoryRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterStatusHistoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_is_bound_to_the_container(): void
    {
        $repository = app(
            OutgoingLetterStatusHistoryRepositoryInterface::class,
        );

        $this->assertInstanceOf(
            OutgoingLetterStatusHistoryRepositoryInterface::class,
            $repository,
        );
    }

    public function test_can_create_status_history(): void
    {
        $repository = app(
            OutgoingLetterStatusHistoryRepositoryInterface::class,
        );

        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OutgoingLetterStatus::DRAFT,
        ]);

        $history = $repository->create([
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::DRAFT,
            'action' => 'created',
        ]);

        $this->assertInstanceOf(
            OutgoingLetterStatusHistory::class,
            $history,
        );

        $this->assertDatabaseHas('outgoing_letter_status_histories', [
            'id' => $history->id,
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::DRAFT->value,
            'action' => 'created',
        ]);
    }

    public function test_can_get_histories_by_outgoing_letter(): void
    {
        $repository = app(
            OutgoingLetterStatusHistoryRepositoryInterface::class,
        );

        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        OutgoingLetterStatusHistory::factory()->create([
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::DRAFT,
            'action' => 'created',
        ]);

        OutgoingLetterStatusHistory::factory()->create([
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::VALIDATED,
            'action' => 'validated',
        ]);

        $histories = $repository->getByOutgoingLetter($letter->id);

        $this->assertCount(2, $histories);

        $this->assertSame(
            OutgoingLetterStatus::DRAFT,
            $histories->first()->status,
        );

        $this->assertSame(
            OutgoingLetterStatus::VALIDATED,
            $histories->last()->status,
        );
    }

    public function test_get_histories_only_returns_histories_for_requested_letter(): void
    {
        $repository = app(
            OutgoingLetterStatusHistoryRepositoryInterface::class,
        );

        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $letter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $otherLetter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        OutgoingLetterStatusHistory::factory()->create([
            'outgoing_letter_id' => $letter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::DRAFT,
            'action' => 'created',
        ]);

        OutgoingLetterStatusHistory::factory()->create([
            'outgoing_letter_id' => $otherLetter->id,
            'changed_by' => $user->id,
            'status' => OutgoingLetterStatus::DRAFT,
            'action' => 'created',
        ]);

        $histories = $repository->getByOutgoingLetter($letter->id);

        $this->assertCount(1, $histories);

        $this->assertSame(
            $letter->id,
            $histories->first()->outgoing_letter_id,
        );
    }
}
