<?php

declare(strict_types=1);

namespace Tests\Feature\LetterTypes;

use App\Enums\LetterTypeStatus;
use App\Models\LetterType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LetterTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTypeMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_letter_type_can_be_created_by_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => null,
            'status' => LetterTypeStatus::ACTIVE,
        ]);

        $this->assertTrue($admin->can('view', $letterType));
        $this->assertTrue($admin->can('update', $letterType));
        $this->assertTrue($letterType->isGlobal());
    }

    public function test_global_letter_type_is_not_editable_by_tenant_admin(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin($tenant)->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => null,
            'status' => LetterTypeStatus::ACTIVE,
        ]);

        $this->assertFalse($admin->can('viewAny', LetterType::class));
        $this->assertFalse($admin->can('view', $letterType));
        $this->assertFalse($admin->can('update', $letterType));
        $this->assertFalse($admin->can('delete', $letterType));
    }

    public function test_global_letter_type_can_be_granted_to_one_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => null,
            'status' => LetterTypeStatus::ACTIVE,
        ]);

        $service = app(LetterTypeService::class);
        $service->grantTenantPermission($letterType, $tenant->id);

        $this->assertTrue($service->isAllowedForTenant($letterType, $tenant->id));
        $this->assertCount(1, $service->getAvailableForTenant($tenant->id)->where('id', $letterType->id));
    }

    public function test_global_letter_type_is_hidden_from_tenant_without_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => null,
            'status' => LetterTypeStatus::ACTIVE,
        ]);

        $service = app(LetterTypeService::class);

        $this->assertFalse($service->isAllowedForTenant($letterType, $tenant->id));
        $this->assertFalse($service->getAvailableForTenant($tenant->id)->contains('id', $letterType->id));
    }

    public function test_permission_can_be_revoked_without_deleting_letter_type(): void
    {
        $tenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => null,
            'status' => LetterTypeStatus::ACTIVE,
        ]);

        $service = app(LetterTypeService::class);
        $service->grantTenantPermission($letterType, $tenant->id);
        $this->assertTrue($service->isAllowedForTenant($letterType, $tenant->id));

        $this->assertTrue($service->revokeTenantPermission($letterType, $tenant->id));
        $this->assertFalse($service->isAllowedForTenant($letterType, $tenant->id));
        $this->assertDatabaseHas('letter_types', [
            'id' => $letterType->id,
            'deleted_at' => null,
        ]);
    }

    public function test_tenant_owned_letter_type_is_available_to_its_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => LetterTypeStatus::ACTIVE,
        ]);

        $service = app(LetterTypeService::class);

        $this->assertTrue($service->isAllowedForTenant($letterType, $tenant->id));
        $this->assertFalse($service->isAllowedForTenant($letterType, $otherTenant->id));
        $this->assertTrue($service->getAvailableForTenant($tenant->id)->contains('id', $letterType->id));
        $this->assertFalse($service->getAvailableForTenant($otherTenant->id)->contains('id', $letterType->id));
    }
}
