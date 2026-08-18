<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\TenantPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProfilePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_view_own_profile(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->for($tenant)
            ->create([
                'role' => UserRole::TENANT_USER,
            ]);

        $policy = new TenantPolicy();

        $this->assertTrue(
            $policy->viewProfile($user, $tenant),
        );
    }

    public function test_tenant_user_cannot_view_other_tenant_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $user = User::factory()
            ->for($tenant)
            ->create([
                'role' => UserRole::TENANT_USER,
            ]);

        $policy = new TenantPolicy();

        $this->assertFalse(
            $policy->viewProfile($user, $otherTenant),
        );
    }

    public function test_tenant_user_can_update_own_profile(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->for($tenant)
            ->create([
                'role' => UserRole::TENANT_USER,
            ]);

        $policy = new TenantPolicy();

        $this->assertTrue(
            $policy->updateProfile($user, $tenant),
        );
    }

    public function test_tenant_user_cannot_update_other_tenant_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $user = User::factory()
            ->for($tenant)
            ->create([
                'role' => UserRole::TENANT_USER,
            ]);

        $policy = new TenantPolicy();

        $this->assertFalse(
            $policy->updateProfile($user, $otherTenant),
        );
    }

    public function test_super_admin_cannot_view_tenant_profile(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'tenant_id' => null,
        ]);

        $policy = new TenantPolicy();

        $this->assertFalse(
            $policy->viewProfile($user, $tenant),
        );
    }

    public function test_super_admin_cannot_update_tenant_profile(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'tenant_id' => null,
        ]);

        $policy = new TenantPolicy();

        $this->assertFalse(
            $policy->updateProfile($user, $tenant),
        );
    }
}
