<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use App\Livewire\TenantProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TenantProfileAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_view_own_organization_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();

        $this->assertTrue($user->can('viewProfile', $tenant));
    }

    public function test_tenant_user_can_view_own_organization_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        $this->assertTrue($user->can('viewProfile', $tenant));
    }

    public function test_tenant_user_cannot_view_another_organizations_profile(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($ownTenant)->create();

        $this->assertFalse($user->can('viewProfile', $otherTenant));
    }

    public function test_tenant_admin_cannot_update_organization_profile_without_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();

        $this->assertFalse($user->can('updateProfile', $tenant));
    }

    public function test_tenant_user_can_update_organization_profile_when_permission_is_granted(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $permission = Permission::query()->where('slug', PermissionEnum::TENANT_PROFILE_UPDATE->value)->firstOrFail();

        $user->roleModel()->permissions()->syncWithoutDetaching([$permission->id]);

        $this->assertTrue($user->can('updateProfile', $tenant));
    }

    public function test_tenant_profile_component_can_update_when_permission_is_granted(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Nama Lama']);
        $user = User::factory()->tenantUser($tenant)->create();
        $permission = Permission::query()->where('slug', PermissionEnum::TENANT_PROFILE_UPDATE->value)->firstOrFail();

        $user->roleModel()->permissions()->syncWithoutDetaching([$permission->id]);

        $this->actingAs($user);

        Livewire::test(TenantProfile::class)
            ->set('name', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Nama Baru',
        ]);
    }

    public function test_tenant_profile_component_can_update_letterhead_when_permission_is_granted(): void
    {
        Storage::fake('public');

        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();
        $permission = Permission::query()->where('slug', PermissionEnum::TENANT_PROFILE_UPDATE->value)->firstOrFail();

        $user->roleModel()->permissions()->syncWithoutDetaching([$permission->id]);

        $this->actingAs($user);

        $file = UploadedFile::fake()->image('letterhead.png', 1200, 300);

        Livewire::test(TenantProfile::class)
            ->set('letterhead', $file)
            ->call('save')
            ->assertHasNoErrors();

        $tenant->refresh();

        $this->assertNotNull($tenant->letterhead_path);
        Storage::disk('public')->assertExists($tenant->letterhead_path);
    }

    public function test_super_admin_can_update_organization_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('updateProfile', $tenant));
    }

    public function test_super_admin_can_view_organization_profile(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->superAdmin()->create();

        $this->assertTrue($user->can('view', $tenant));
    }
}
