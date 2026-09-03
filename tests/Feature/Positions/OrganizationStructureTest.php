<?php

declare(strict_types=1);

namespace Tests\Feature\Positions;

use App\Enums\PositionAssignmentStatus;
use App\Enums\PositionType;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\TenantPositionStructure;
use App\Models\User;
use App\Services\PositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class OrganizationStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_managerial_position_can_have_multiple_active_holders(): void
    {
        $tenant = Tenant::factory()->create();
        $first = User::factory()->tenantUser($tenant)->create();
        $second = User::factory()->tenantUser($tenant)->create();
        $position = Position::factory()->forCategory($tenant->category)->jft()->create();
        $service = app(PositionService::class);

        $service->assignHolder($position, $first->id, now()->subDay());
        $service->assignHolder($position, $second->id, now());

        $this->assertDatabaseCount('position_holders', 2);
        $this->assertCount(2, $service->getActiveHolders($position));
    }

    public function test_managerial_position_keeps_only_one_active_holder(): void
    {
        $tenant = Tenant::factory()->create();
        $first = User::factory()->tenantUser($tenant)->create();
        $second = User::factory()->tenantUser($tenant)->create();
        $position = Position::factory()->forCategory($tenant->category)->managerial()->create();
        $service = app(PositionService::class);

        $service->assignHolder($position, $first->id, now()->subDay(), 'definitif');
        $service->assignHolder($position, $second->id, now(), 'plt');

        $this->assertDatabaseCount('position_holders', 2);
        $this->assertSame($second->id, $service->getActiveHolder($position)?->user_id);
        $this->assertSame('plt', $service->getActiveHolder($position)?->assignment_status);
    }

    public function test_holder_can_store_assignment_status_and_sk_metadata(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $position = Position::factory()->forCategory($tenant->category)->managerial()->create();
        $service = app(PositionService::class);

        $holder = $service->assignHolder($position, $user->id, now(), PositionAssignmentStatus::PJ, '800.1.3.3/123/BKPSDM/2026', 'position-appointments/test/sk.pdf');

        $this->assertSame('pj', $holder->assignment_status);
        $this->assertSame('800.1.3.3/123/BKPSDM/2026', $holder->appointment_number);
        $this->assertSame('position-appointments/test/sk.pdf', $holder->appointment_document_path);
        $this->assertDatabaseHas('position_holders', ['id' => $holder->id, 'assignment_status' => 'pj', 'appointment_number' => '800.1.3.3/123/BKPSDM/2026']);
    }

    public function test_managerial_position_accepts_all_supported_temporary_statuses(): void
    {
        $tenant = Tenant::factory()->create();
        $position = Position::factory()->forCategory($tenant->category)->managerial()->create();
        $service = app(PositionService::class);

        foreach ([PositionAssignmentStatus::PLT, PositionAssignmentStatus::PLH, PositionAssignmentStatus::PJ, PositionAssignmentStatus::PJS] as $index => $status) {
            $user = User::factory()->tenantUser($tenant)->create();
            $service->assignHolder($position, $user->id, now()->addDays($index), $status, 'SK-'.$status->value.'-'.$index);
        }

        $this->assertSame('pjs', $service->getActiveHolder($position)?->assignment_status);
    }

    public function test_tenant_structure_can_assign_a_position_below_another_position(): void
    {
        $tenant = Tenant::factory()->create();
        $lurah = Position::factory()->forCategory($tenant->category)->managerial()->create(['name' => 'Lurah']);
        $kasi = Position::factory()->forCategory($tenant->category)->managerial()->create(['name' => 'Kasi Pemerintahan']);

        TenantPositionStructure::create(['tenant_id' => $tenant->id, 'position_id' => $lurah->id, 'parent_position_id' => null, 'sort_order' => 0, 'is_root' => true]);
        TenantPositionStructure::create(['tenant_id' => $tenant->id, 'position_id' => $kasi->id, 'parent_position_id' => $lurah->id, 'sort_order' => 1, 'is_root' => false]);

        $this->assertDatabaseHas('tenant_position_structures', ['tenant_id' => $tenant->id, 'position_id' => $kasi->id, 'parent_position_id' => $lurah->id, 'is_root' => false]);
    }

    public function test_non_managerial_duplicate_assignment_for_same_user_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $position = Position::factory()->forCategory($tenant->category)->jfu()->create();
        $service = app(PositionService::class);

        $service->assignHolder($position, $user->id, now()->subDay());

        $this->expectException(LogicException::class);
        $service->assignHolder($position, $user->id, now());
    }
}
