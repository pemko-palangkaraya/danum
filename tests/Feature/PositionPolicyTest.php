<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PositionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\PositionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PositionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PositionPolicy();
    }

    public function test_active_tenant_user_can_view_any_position(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertTrue(
            $this->policy->viewAny($user)
        );
    }

    public function test_inactive_tenant_user_cannot_view_any_position(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::INACTIVE,
        ]);

        $this->assertFalse(
            $this->policy->viewAny($user)
        );
    }

    public function test_super_admin_can_view_position_from_any_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue(
            $this->policy->view($admin, $position)
        );
    }

    public function test_tenant_user_can_view_position_from_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue(
            $this->policy->view($user, $position)
        );
    }

    public function test_tenant_user_cannot_view_position_from_different_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $this->assertFalse(
            $this->policy->view($user, $position)
        );
    }

    public function test_inactive_tenant_user_cannot_view_position(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::INACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertFalse(
            $this->policy->view($user, $position)
        );
    }

    public function test_active_tenant_user_can_create_position(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertTrue(
            $this->policy->create($user)
        );
    }

    public function test_inactive_tenant_user_cannot_create_position(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::INACTIVE,
        ]);

        $this->assertFalse(
            $this->policy->create($user)
        );
    }

    public function test_super_admin_can_create_position(): void
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->assertTrue(
            $this->policy->create($admin)
        );
    }

    public function test_super_admin_can_update_position_from_any_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue(
            $this->policy->update($admin, $position)
        );
    }

    public function test_tenant_user_can_update_position_from_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue(
            $this->policy->update($user, $position)
        );
    }

    public function test_tenant_user_cannot_update_position_from_different_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $this->assertFalse(
            $this->policy->update($user, $position)
        );
    }

    public function test_tenant_user_can_delete_position_from_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue(
            $this->policy->delete($user, $position)
        );
    }

    public function test_tenant_user_cannot_delete_position_from_different_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $this->assertFalse(
            $this->policy->delete($user, $position)
        );
    }

    public function test_tenant_user_can_restore_position_from_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue(
            $this->policy->restore($user, $position)
        );
    }

    public function test_tenant_user_cannot_restore_position_from_different_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $this->assertFalse(
            $this->policy->restore($user, $position)
        );
    }

    public function test_inactive_tenant_user_cannot_update_delete_or_restore_position(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::INACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertFalse(
            $this->policy->update($user, $position)
        );

        $this->assertFalse(
            $this->policy->delete($user, $position)
        );

        $this->assertFalse(
            $this->policy->restore($user, $position)
        );
    }
}
