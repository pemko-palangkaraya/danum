<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LetterType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTypePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_view_any_letter_types(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertTrue(
            $user->can('viewAny', LetterType::class),
        );
    }

    public function test_tenant_user_can_view_own_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertTrue(
            $user->can('view', $letterType),
        );
    }

    public function test_tenant_user_cannot_view_other_tenant_letter_type(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($ownTenant)
            ->create();

        $this->assertFalse(
            $user->can('view', $letterType),
        );
    }

    public function test_tenant_user_can_create_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertTrue(
            $user->can('create', LetterType::class),
        );
    }

    public function test_tenant_user_can_update_own_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertTrue(
            $user->can('update', $letterType),
        );
    }

    public function test_tenant_user_cannot_update_other_tenant_letter_type(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($ownTenant)
            ->create();

        $this->assertFalse(
            $user->can('update', $letterType),
        );
    }

    public function test_tenant_user_can_delete_own_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertTrue(
            $user->can('delete', $letterType),
        );
    }

    public function test_tenant_user_cannot_delete_other_tenant_letter_type(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($ownTenant)
            ->create();

        $this->assertFalse(
            $user->can('delete', $letterType),
        );
    }

    public function test_tenant_user_can_restore_own_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($tenant)
            ->create();

        $this->assertTrue(
            $user->can('restore', $letterType),
        );
    }

    public function test_tenant_user_cannot_restore_other_tenant_letter_type(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $user = User::factory()
            ->tenantUser($ownTenant)
            ->create();

        $this->assertFalse(
            $user->can('restore', $letterType),
        );
    }

    public function test_super_admin_cannot_view_any_letter_types(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $this->assertFalse(
            $user->can('viewAny', LetterType::class),
        );
    }

    public function test_super_admin_cannot_create_letter_type(): void
    {
        $user = User::factory()
            ->superAdmin()
            ->create();

        $this->assertFalse(
            $user->can('create', LetterType::class),
        );
    }

    public function test_super_admin_cannot_view_letter_type(): void
    {
        $tenant = Tenant::factory()->create();

        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()
            ->superAdmin()
            ->create();

        $this->assertFalse(
            $user->can('view', $letterType),
        );
    }
}
