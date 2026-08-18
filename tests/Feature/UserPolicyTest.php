<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy();
    }

    public function test_super_admin_can_view_any_users(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $this->assertTrue(
            $this->policy->viewAny($user),
        );
    }

    public function test_tenant_user_cannot_view_any_users(): void
    {
        $user = User::factory()
            ->tenantUser()
            ->create();

        $this->assertFalse(
            $this->policy->viewAny($user),
        );
    }

    public function test_super_admin_can_view_any_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $targetUser = User::factory()->create();

        $this->assertTrue(
            $this->policy->view($superAdmin, $targetUser),
        );
    }

    public function test_tenant_user_can_view_user_in_the_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $targetUser = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertTrue(
            $this->policy->view($user, $targetUser),
        );
    }

    public function test_tenant_user_cannot_view_user_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenantA)
            ->create();

        $targetUser = User::factory()
            ->tenantUser($tenantB)
            ->create();

        $this->assertFalse(
            $this->policy->view($user, $targetUser),
        );
    }

    public function test_tenant_user_cannot_view_super_admin(): void
    {
        $tenantUser = User::factory()
            ->tenantUser()
            ->create();

        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $this->assertFalse(
            $this->policy->view($tenantUser, $superAdmin),
        );
    }

    public function test_super_admin_can_create_users(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $this->assertTrue(
            $this->policy->create($superAdmin),
        );
    }

    public function test_tenant_user_cannot_create_users(): void
    {
        $tenantUser = User::factory()
            ->tenantUser()
            ->create();

        $this->assertFalse(
            $this->policy->create($tenantUser),
        );
    }

    public function test_super_admin_can_update_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $targetUser = User::factory()->create();

        $this->assertTrue(
            $this->policy->update($superAdmin, $targetUser),
        );
    }

    public function test_tenant_user_cannot_update_user(): void
    {
        $tenantUser = User::factory()
            ->tenantUser()
            ->create();

        $targetUser = User::factory()->create();

        $this->assertFalse(
            $this->policy->update($tenantUser, $targetUser),
        );
    }

    public function test_super_admin_can_delete_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $targetUser = User::factory()->create();

        $this->assertTrue(
            $this->policy->delete($superAdmin, $targetUser),
        );
    }

    public function test_tenant_user_cannot_delete_user(): void
    {
        $tenantUser = User::factory()
            ->tenantUser()
            ->create();

        $targetUser = User::factory()->create();

        $this->assertFalse(
            $this->policy->delete($tenantUser, $targetUser),
        );
    }

    public function test_super_admin_can_force_delete_user(): void
    {
        $superAdmin = User::factory()
            ->superAdmin()
            ->create();

        $targetUser = User::factory()->create();

        $this->assertTrue(
            $this->policy->forceDelete($superAdmin, $targetUser),
        );
    }

    public function test_tenant_user_cannot_force_delete_user(): void
    {
        $tenantUser = User::factory()
            ->tenantUser()
            ->create();

        $targetUser = User::factory()->create();

        $this->assertFalse(
            $this->policy->forceDelete($tenantUser, $targetUser),
        );
    }
}