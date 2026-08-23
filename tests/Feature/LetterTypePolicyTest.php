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

    public function test_super_admin_can_view_any_letter_types(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('viewAny', LetterType::class));
    }

    public function test_super_admin_can_view_global_letter_type(): void
    {
        $letterType = LetterType::factory()->create(['tenant_id' => null]);
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('view', $letterType));
    }

    public function test_super_admin_can_create_letter_type(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('create', LetterType::class));
    }

    public function test_super_admin_can_update_global_letter_type(): void
    {
        $letterType = LetterType::factory()->create(['tenant_id' => null]);
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('update', $letterType));
    }

    public function test_super_admin_can_delete_global_letter_type(): void
    {
        $letterType = LetterType::factory()->create(['tenant_id' => null]);
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('delete', $letterType));
    }

    public function test_super_admin_can_restore_global_letter_type(): void
    {
        $letterType = LetterType::factory()->create(['tenant_id' => null]);
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('restore', $letterType));
    }

    public function test_super_admin_cannot_manage_tenant_scoped_letter_type(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->superAdmin()->create();

        $this->assertFalse($user->can('view', $letterType));
        $this->assertFalse($user->can('update', $letterType));
        $this->assertFalse($user->can('delete', $letterType));
        $this->assertFalse($user->can('restore', $letterType));
    }

    public function test_tenant_user_cannot_manage_letter_types(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $globalLetterType = LetterType::factory()->create(['tenant_id' => null]);

        $this->assertFalse($user->can('viewAny', LetterType::class));
        $this->assertFalse($user->can('view', $globalLetterType));
        $this->assertFalse($user->can('create', LetterType::class));
        $this->assertFalse($user->can('update', $globalLetterType));
        $this->assertFalse($user->can('delete', $globalLetterType));
        $this->assertFalse($user->can('restore', $globalLetterType));
    }
}
