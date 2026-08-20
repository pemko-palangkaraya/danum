<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\Tenant;
use App\Repositories\Contracts\PositionRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PositionRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(PositionRepositoryInterface::class);
    }

    public function test_it_can_find_position_by_id(): void
    {
        $position = Position::factory()->create();

        $result = $this->repository->find($position->id);

        $this->assertNotNull($result);
        $this->assertTrue($result->is($position));
    }

    public function test_it_can_find_position_by_code_within_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'LURAH',
        ]);

        $result = $this->repository->findByCode(
            $tenant->id,
            'LURAH'
        );

        $this->assertNotNull($result);
        $this->assertTrue($result->is($position));
    }

    public function test_find_by_code_does_not_cross_tenant_boundary(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $position = Position::factory()->create([
            'tenant_id' => $tenantB->id,
            'code' => 'LURAH',
        ]);

        $result = $this->repository->findByCode(
            $tenantA->id,
            'LURAH'
        );

        $this->assertNull($result);
    }

    public function test_it_can_get_all_positions_for_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        Position::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
        ]);

        Position::factory()->create();

        $result = $this->repository->getAll($tenant->id);

        $this->assertCount(3, $result);

        $this->assertTrue(
            $result->every(
                fn(Position $position): bool =>
                $position->tenant_id === $tenant->id
            )
        );
    }

    public function test_get_all_does_not_cross_tenant_boundary(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Position::factory()->count(2)->create([
            'tenant_id' => $tenantA->id,
        ]);

        Position::factory()->count(3)->create([
            'tenant_id' => $tenantB->id,
        ]);

        $result = $this->repository->getAll($tenantA->id);

        $this->assertCount(2, $result);
    }

    public function test_it_can_create_position(): void
    {
        $tenant = Tenant::factory()->create();

        $position = $this->repository->create([
            'tenant_id' => $tenant->id,
            'code' => 'SEKRETARIS',
            'name' => 'Sekretaris',
            'description' => 'Sekretaris Kelurahan',
            'status' => PositionStatus::ACTIVE,
        ]);

        $this->assertInstanceOf(Position::class, $position);

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'tenant_id' => $tenant->id,
            'code' => 'SEKRETARIS',
        ]);
    }

    public function test_it_can_update_position(): void
    {
        $position = Position::factory()->create([
            'name' => 'Lurah',
        ]);

        $result = $this->repository->update($position, [
            'name' => 'Lurah Mungku Baru',
        ]);

        $this->assertSame(
            'Lurah Mungku Baru',
            $result->name
        );

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'name' => 'Lurah Mungku Baru',
        ]);
    }

    public function test_it_can_soft_delete_position(): void
    {
        $position = Position::factory()->create();

        $result = $this->repository->delete($position);

        $this->assertTrue($result);

        $this->assertSoftDeleted('positions', [
            'id' => $position->id,
        ]);
    }

    public function test_find_does_not_return_soft_deleted_position(): void
    {
        $position = Position::factory()->create();

        $this->repository->delete($position);

        $result = $this->repository->find($position->id);

        $this->assertNull($result);
    }

    public function test_it_can_find_soft_deleted_position(): void
    {
        $position = Position::factory()->create();

        $this->repository->delete($position);

        $result = $this->repository->findWithTrashed(
            $position->id
        );

        $this->assertNotNull($result);
        $this->assertTrue($result->is($position));
        $this->assertNotNull($result->deleted_at);
    }

    public function test_it_can_restore_position(): void
    {
        $position = Position::factory()->create();

        $this->repository->delete($position);

        $deleted = $this->repository->findWithTrashed(
            $position->id
        );

        $this->assertNotNull($deleted);

        $result = $this->repository->restore($deleted);

        $this->assertTrue($result);

        $restored = $this->repository->find($position->id);

        $this->assertNotNull($restored);
        $this->assertNull($restored->deleted_at);
    }
}
