<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_tenant_user_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::TENANT_USER, $user->role);
        $this->assertNotNull($user->tenant_id);
    }

    public function test_it_creates_a_super_admin_without_a_tenant(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $this->assertSame(UserRole::SUPER_ADMIN, $user->role);
        $this->assertNull($user->tenant_id);
    }

    public function test_it_creates_a_tenant_user_for_the_specified_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertSame(UserRole::TENANT_USER, $user->role);
        $this->assertSame($tenant->id, $user->tenant_id);
    }

    public function test_it_creates_a_tenant_user_with_a_new_tenant_when_no_tenant_is_specified(): void
    {
        $user = User::factory()
            ->tenantUser()
            ->create();

        $this->assertSame(UserRole::TENANT_USER, $user->role);
        $this->assertNotNull($user->tenant_id);
    }

    public function test_it_creates_an_unverified_user(): void
    {
        $user = User::factory()
            ->unverified()
            ->create();

        $this->assertNull($user->email_verified_at);
    }

    public function test_it_creates_a_valid_tenant_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(Tenant::class, $user->tenant);
        $this->assertSame($user->tenant_id, $user->tenant->id);
    }
}