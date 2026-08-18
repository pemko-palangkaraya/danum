<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_is_outside_tenant_boundary(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $this->assertSame(
            UserRole::SUPER_ADMIN,
            $user->role,
        );

        $this->assertNull($user->tenant_id);
    }

    public function test_tenant_user_belongs_to_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertSame(
            UserRole::TENANT_USER,
            $user->role,
        );

        $this->assertSame(
            $tenant->id,
            $user->tenant_id,
        );
    }

    public function test_tenant_user_can_resolve_tenant_relationship(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertTrue(
            $user->tenant->is($tenant),
        );
    }

    public function test_tenant_can_resolve_users_relationship(): void
    {
        $tenant = Tenant::factory()->create();

        User::factory()
            ->count(2)
            ->tenantUser($tenant)
            ->create();

        $this->assertCount(
            2,
            $tenant->users,
        );
    }
}