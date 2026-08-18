<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantService $service;

    private TenantRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(
            TenantRepositoryInterface::class,
        );

        $this->service = new TenantService(
            $this->repository,
        );
    }

    public function test_service_can_find_tenant_by_id(): void
    {
        $tenant = Tenant::factory()->make([
            'id' => (string) Str::uuid(),
        ]);

        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($tenant->id)
            ->andReturn($tenant);

        $result = $this->service->find($tenant->id);

        $this->assertSame($tenant, $result);
    }

    public function test_service_can_find_tenant_by_code(): void
    {
        $tenant = Tenant::factory()->make();

        $this->repository
            ->shouldReceive('findByCode')
            ->once()
            ->with($tenant->code)
            ->andReturn($tenant);

        $result = $this->service->findByCode($tenant->code);

        $this->assertSame($tenant, $result);
    }

    public function test_service_can_get_all_tenants(): void
    {
        $tenants = Tenant::factory()
            ->count(3)
            ->make();

        $collection = new Collection($tenants);

        $this->repository
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($collection);

        $result = $this->service->getAll();

        $this->assertSame($collection, $result);
        $this->assertCount(3, $result);
    }

    public function test_service_can_create_tenant_through_repository(): void
    {
        $tenant = Tenant::factory()->make();

        $data = [
            'code' => $tenant->code,
            'name' => $tenant->name,
            'province' => $tenant->province,
            'city' => $tenant->city,
            'district' => $tenant->district,
            'village' => $tenant->village,
            'status' => TenantStatus::ACTIVE,
        ];

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($tenant);

        $result = $this->service->create($data);

        $this->assertSame($tenant, $result);
    }

    public function test_service_can_update_tenant_through_repository(): void
    {
        $tenant = Tenant::factory()->make();

        $data = [
            'name' => 'Updated Tenant',
        ];

        $updatedTenant = Tenant::factory()->make([
            'id' => $tenant->id,
            'name' => 'Updated Tenant',
        ]);

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with($tenant, $data)
            ->andReturn($updatedTenant);

        $result = $this->service->update($tenant, $data);

        $this->assertSame($updatedTenant, $result);
    }

    public function test_service_can_delete_tenant_through_repository(): void
    {
        $tenant = Tenant::factory()->make();

        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with($tenant)
            ->andReturn(true);

        $result = $this->service->delete($tenant);

        $this->assertTrue($result);
    }

    public function test_service_can_restore_tenant_through_repository(): void
    {
        $tenant = Tenant::factory()->make();

        $this->repository
            ->shouldReceive('restore')
            ->once()
            ->with($tenant)
            ->andReturn(true);

        $result = $this->service->restore($tenant);

        $this->assertTrue($result);
    }

    public function test_service_does_not_persist_directly(): void
    {
        $tenant = Tenant::factory()->make();

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with($tenant, ['name' => 'Updated Tenant'])
            ->andReturn($tenant);

        $result = $this->service->update(
            $tenant,
            ['name' => 'Updated Tenant'],
        );

        $this->assertSame($tenant, $result);
        $this->assertFalse($tenant->exists);
    }

    public function test_service_can_find_thrased_tenant_through_repository(): void
    {
        $tenant = Tenant::factory()->make(
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
            ]
        );

        $this->repository
            ->shouldReceive('findWithTrashed')
            ->once()
            ->with($tenant->id)
            ->andReturn($tenant);

        $result = $this->service->findWithTrashed($tenant->id);

        $this->assertSame($tenant, $result);
    }
}