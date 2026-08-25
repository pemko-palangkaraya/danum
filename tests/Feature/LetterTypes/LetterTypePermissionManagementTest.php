<?php

declare(strict_types=1);

namespace Tests\Feature\LetterTypes;

use App\Models\LetterType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LetterTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTypePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_grant_and_revoke_tenant_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $letterType = LetterType::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)
            ->postJson('/api/letter-types/'.$letterType->id.'/permissions', ['tenant_id' => $tenant->id])
            ->assertCreated()
            ->assertJsonPath('data.tenant_id', $tenant->id);

        $this->assertTrue(app(LetterTypeService::class)->isAllowedForTenant($letterType->fresh(), $tenant->id));

        $this->actingAs($admin)
            ->deleteJson('/api/letter-types/'.$letterType->id.'/permissions/'.$tenant->id)
            ->assertOk();

        $this->assertFalse(app(LetterTypeService::class)->isAllowedForTenant($letterType->fresh(), $tenant->id));
    }

    public function test_tenant_admin_cannot_manage_letter_type_permissions(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin($tenant)->create();
        $letterType = LetterType::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)
            ->postJson('/api/letter-types/'.$letterType->id.'/permissions', ['tenant_id' => $tenant->id])
            ->assertForbidden();
    }

    public function test_permission_list_is_available_to_super_admin_only(): void
    {
        $tenant = Tenant::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $tenantAdmin = User::factory()->tenantAdmin($tenant)->create();
        $letterType = LetterType::factory()->create(['tenant_id' => null]);

        $this->actingAs($superAdmin)
            ->getJson('/api/letter-types/'.$letterType->id.'/permissions')
            ->assertOk();

        $this->actingAs($tenantAdmin)
            ->getJson('/api/letter-types/'.$letterType->id.'/permissions')
            ->assertForbidden();
    }

    public function test_duplicate_permission_is_idempotent(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $letterType = LetterType::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)->postJson('/api/letter-types/'.$letterType->id.'/permissions', ['tenant_id' => $tenant->id])->assertCreated();
        $this->actingAs($admin)->postJson('/api/letter-types/'.$letterType->id.'/permissions', ['tenant_id' => $tenant->id])->assertCreated();

        $this->assertDatabaseCount('letter_type_permissions', 1);
    }
}
