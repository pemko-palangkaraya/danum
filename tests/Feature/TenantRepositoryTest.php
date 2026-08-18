<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TenantRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(
            TenantRepositoryInterface::class,
        );
    }

    public function test_repository_can_find_tenant_by_id(): void
    {
        $tenant = Tenant::factory()->create();

        $result = $this->repository->find($tenant->id);

        $this->assertNotNull($result);
        $this->assertSame($tenant->id, $result->id);
    }

    public function test_repository_can_find_tenant_by_code(): void
    {
        $tenant = Tenant::factory()->create();

        $result = $this->repository->findByCode($tenant->code);

        $this->assertNotNull($result);
        $this->assertSame($tenant->id, $result->id);
        $this->assertSame($tenant->code, $result->code);
    }

    public function test_repository_can_return_all_tenants(): void
    {
        Tenant::factory()->count(3)->create();

        $result = $this->repository->getAll();

        $this->assertCount(3, $result);
    }

    public function test_repository_can_create_tenant(): void
    {
        $tenant = $this->repository->create(
            Tenant::factory()->make()->toArray(),
        );

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertNotNull($tenant->id);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'code' => $tenant->code,
            'name' => $tenant->name,
        ]);
    }

    public function test_repository_can_update_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $updatedName = 'Updated Tenant Name';

        $result = $this->repository->update(
            $tenant,
            [
                'name' => $updatedName,
            ],
        );

        $this->assertSame($tenant->id, $result->id);
        $this->assertSame($updatedName, $result->name);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => $updatedName,
        ]);
    }

    public function test_repository_can_delete_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $result = $this->repository->delete($tenant);

        $this->assertTrue($result);

        $this->assertSoftDeleted('tenants', [
            'id' => $tenant->id,
        ]);
    }

    public function test_repository_can_restore_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $tenant->delete();

        $result = $this->repository->restore($tenant);

        $this->assertTrue($result);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
        ]);

        $this->assertNull(
            Tenant::withTrashed()
                ->find($tenant->id)
                ->deleted_at,
        );
    }

    public function test_repository_can_handle_missing_tenant(): void
    {
        $result = $this->repository->find(
            '00000000-0000-0000-0000-000000000000',
        );

        $this->assertNull($result);
    }

    public function test_repository_can_handle_missing_code(): void
    {
        $result = $this->repository->findByCode(
            'NONEXISTENT',
        );

        $this->assertNull($result);
    }

    public function test_repository_preserves_tenant_status(): void
    {
        $tenant = Tenant::factory()
            ->inactive()
            ->create();

        $result = $this->repository->find($tenant->id);

        $this->assertNotNull($result);
        $this->assertSame(
            TenantStatus::INACTIVE,
            $result->status,
        );
    }

        public function test_repository_can_find_trashed_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $tenant->delete();

        $result = $this->repository->findWithTrashed($tenant->id);

        $this->assertNotNull($result);
        $this->assertSame($tenant->id, $result->id);
        $this->assertNotNull($result->deleted_at);
}
    
}