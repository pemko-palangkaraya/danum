<?php

declare(strict_types=1);

namespace Tests\Feature\LetterTypes;

use App\Livewire\LetterTypes\Permissions;
use App\Models\LetterType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LetterTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_permission_changes_are_audited(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $letterType = LetterType::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin)
            ->postJson('/api/letter-types/'.$letterType->id.'/permissions', ['tenant_id' => $tenant->id])
            ->assertCreated();

        $permission = $letterType->permissions()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'action' => 'letter_type.permission.granted',
            'auditable_type' => $permission::class,
            'auditable_id' => $permission->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson('/api/letter-types/'.$letterType->id.'/permissions/'.$tenant->id)
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'action' => 'letter_type.permission.revoked',
            'auditable_type' => $permission::class,
            'auditable_id' => $permission->id,
        ]);
    }

    public function test_permission_changes_from_livewire_page_are_audited(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $letterType = LetterType::factory()->create(['tenant_id' => null]);

        $this->actingAs($admin);

        Livewire::test(Permissions::class, ['letterType' => $letterType->id])
            ->call('grant', $tenant->id);

        $permission = $letterType->permissions()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'action' => 'letter_type.permission.granted',
            'auditable_type' => $permission::class,
            'auditable_id' => $permission->id,
        ]);

        Livewire::test(Permissions::class, ['letterType' => $letterType->id])
            ->call('confirmRevoke', $tenant->id)
            ->call('revoke');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'action' => 'letter_type.permission.revoked',
            'auditable_type' => $permission::class,
            'auditable_id' => $permission->id,
        ]);
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

    public function test_category_permission_applies_to_all_tenants_in_that_category(): void
    {
        $category = \App\Models\TenantCategory::query()->where('code', 'kelurahan')->firstOrFail();
        $kelurahanA = Tenant::factory()->create(['tenant_category_id' => $category->id]);
        $kelurahanB = Tenant::factory()->create(['tenant_category_id' => $category->id]);
        $dinas = Tenant::factory()->create([
            'tenant_category_id' => \App\Models\TenantCategory::query()->where('code', 'dinas')->value('id'),
        ]);

        $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => 'active']);

        app(LetterTypeService::class)->grantCategoryPermission($letterType, $category->id);

        $service = app(LetterTypeService::class);

        $this->assertTrue($service->isAllowedForTenant($letterType->fresh(), $kelurahanA->id));
        $this->assertTrue($service->isAllowedForTenant($letterType->fresh(), $kelurahanB->id));
        $this->assertFalse($service->isAllowedForTenant($letterType->fresh(), $dinas->id));

        $this->assertContains(
            $letterType->id,
            $service->getAvailableForTenant($kelurahanA->id)->pluck('id')->all(),
        );
    }

    public function test_category_permission_can_be_revoked(): void
    {
        $category = \App\Models\TenantCategory::query()->where('code', 'kecamatan')->firstOrFail();
        $tenant = Tenant::factory()->create(['tenant_category_id' => $category->id]);
        $letterType = LetterType::factory()->create(['tenant_id' => null, 'status' => 'active']);
        $service = app(LetterTypeService::class);

        $service->grantCategoryPermission($letterType, $category->id);
        $this->assertTrue($service->isAllowedForTenant($letterType->fresh(), $tenant->id));

        $service->revokeCategoryPermission($letterType, $category->id);
        $this->assertFalse($service->isAllowedForTenant($letterType->fresh(), $tenant->id));
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
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_available_for_tenant_returns_only_active_allowed_global_types_and_own_types(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $allowed = LetterType::factory()->create(['tenant_id' => null, 'status' => 'active', 'code' => 'ALLOWED']);
        $denied = LetterType::factory()->create(['tenant_id' => null, 'status' => 'active', 'code' => 'DENIED']);
        $inactiveAllowed = LetterType::factory()->create(['tenant_id' => null, 'status' => 'draft', 'code' => 'INACTIVE']);
        $own = LetterType::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active', 'code' => 'OWN']);
        $otherOwn = LetterType::factory()->create(['tenant_id' => $otherTenant->id, 'status' => 'active', 'code' => 'OTHER']);

        app(LetterTypeService::class)->grantTenantPermission($allowed, $tenant->id);
        app(LetterTypeService::class)->grantTenantPermission($inactiveAllowed, $tenant->id);

        $codes = app(LetterTypeService::class)
            ->getAvailableForTenant($tenant->id)
            ->pluck('code')
            ->all();

        $this->assertContains('ALLOWED', $codes);
        $this->assertContains('OWN', $codes);
        $this->assertNotContains('DENIED', $codes);
        $this->assertNotContains('INACTIVE', $codes);
        $this->assertNotContains('OTHER', $codes);
    }
}
