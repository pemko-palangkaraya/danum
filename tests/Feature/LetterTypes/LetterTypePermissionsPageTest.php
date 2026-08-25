<?php

declare(strict_types=1);

namespace Tests\Feature\LetterTypes;

use App\Models\LetterType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTypePermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_global_letter_type_permissions_page(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $letterType = LetterType::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)
            ->get(route('letter-types.permissions', $letterType))
            ->assertOk()
            ->assertSee('Atur Akses OPD')
            ->assertSee($letterType->name);
    }

    public function test_tenant_user_cannot_open_letter_type_permissions_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        $letterType = LetterType::factory()->create(['tenant_id' => null]);

        $this->actingAs($user)
            ->get(route('letter-types.permissions', $letterType))
            ->assertForbidden();
    }

    public function test_tenant_owned_letter_type_is_not_managed_as_global_permission_target(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)
            ->get(route('letter-types.permissions', $letterType))
            ->assertNotFound();
    }
}
