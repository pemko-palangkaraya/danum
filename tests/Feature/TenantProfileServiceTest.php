<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\TenantProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TenantProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_show_tenant_profile(): void
    {
        $tenant = Tenant::factory()->create();

        $repository = Mockery::mock(TenantRepositoryInterface::class);

        $service = new TenantProfileService($repository);

        $result = $service->show($tenant);

        $this->assertSame($tenant, $result);
    }

    public function test_it_updates_only_profile_fields(): void
    {
        $tenant = Tenant::factory()->create();

        $repository = Mockery::mock(TenantRepositoryInterface::class);

        $repository
            ->shouldReceive('update')
            ->once()
            ->with(
                $tenant,
                [
                    'name' => 'Kelurahan Baru',
                    'address' => 'Jalan Baru',
                    'phone' => '08123456789',
                    'email' => 'baru@example.com',
                ],
            )
            ->andReturn($tenant);

        $service = new TenantProfileService($repository);

        $result = $service->update($tenant, [
            'name' => 'Kelurahan Baru',
            'address' => 'Jalan Baru',
            'phone' => '08123456789',
            'email' => 'baru@example.com',
            'code' => 'SHOULD-NOT-CHANGE',
            'status' => false,
        ]);

        $this->assertSame($tenant, $result);
    }

    public function test_it_delegates_update_to_repository(): void
    {
        $tenant = Tenant::factory()->create();

        $repository = Mockery::mock(TenantRepositoryInterface::class);

        $repository
            ->shouldReceive('update')
            ->once()
            ->with(
                $tenant,
                [
                    'name' => 'Kelurahan Baru',
                ],
            )
            ->andReturn($tenant);

        $service = new TenantProfileService($repository);

        $result = $service->update($tenant, [
            'name' => 'Kelurahan Baru',
        ]);

        $this->assertSame($tenant, $result);
    }
}
