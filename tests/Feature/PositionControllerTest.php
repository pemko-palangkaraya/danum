<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PositionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Illuminate\Routing\Middleware\SubstituteBindings;

class PositionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([
            'auth',
            SubstituteBindings::class,
        ])->group(function (): void {
            Route::apiResource(
                'positions',
                \App\Http\Controllers\PositionController::class
            )->except(['create', 'edit']);

            Route::post(
                'positions/{position}/restore',
                [\App\Http\Controllers\PositionController::class, 'restore']
            );

            Route::post(
                'positions/{position}/holder',
                [\App\Http\Controllers\PositionController::class, 'assignHolder']
            );

            Route::post(
                'positions/{position}/holder/end',
                [\App\Http\Controllers\PositionController::class, 'endHolder']
            );

            Route::get(
                'positions/{position}/holder',
                [\App\Http\Controllers\PositionController::class, 'activeHolder']
            );

            Route::get(
                'positions/{position}/holders',
                [\App\Http\Controllers\PositionController::class, 'holderHistory']
            );
        });
    }

    public function test_can_list_positions(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        Position::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/positions');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_can_create_position(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/positions', [
                'code' => 'POS-001',
                'name' => 'Lurah',
                'description' => 'Kepala Kelurahan',
                'status' => PositionStatus::ACTIVE->value,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.code', 'POS-001')
            ->assertJsonPath('data.name', 'Lurah');

        $this->assertDatabaseHas('positions', [
            'tenant_id' => $tenant->id,
            'code' => 'POS-001',
            'name' => 'Lurah',
        ]);
    }

    public function test_create_position_uses_authenticated_user_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $otherTenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/positions', [
                'tenant_id' => $otherTenant->id,
                'code' => 'POS-001',
                'name' => 'Lurah',
                'status' => PositionStatus::ACTIVE->value,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('positions', [
            'tenant_id' => $tenant->id,
            'code' => 'POS-001',
        ]);

        $this->assertDatabaseMissing('positions', [
            'tenant_id' => $otherTenant->id,
            'code' => 'POS-001',
        ]);
    }

    public function test_store_position_validates_required_fields(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/positions', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
                'name',
                'status',
            ]);
    }

    public function test_can_show_position(): void
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

        $response = $this->actingAs($user)
            ->getJson("/positions/{$position->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $position->id);
    }

    public function test_can_update_position(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Lurah',
        ]);

        $response = $this->actingAs($user)
            ->patchJson("/positions/{$position->id}", [
                'name' => 'Lurah Mungku Baru',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Lurah Mungku Baru');

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'name' => 'Lurah Mungku Baru',
        ]);
    }

    public function test_can_delete_position(): void
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

        $response = $this->actingAs($user)
            ->deleteJson("/positions/{$position->id}");

        $response->assertOk();

        $this->assertSoftDeleted('positions', [
            'id' => $position->id,
        ]);
    }

    public function test_can_restore_position(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()
            ->create([
                'tenant_id' => $tenant->id,
            ]);

        $position->delete();

        $response = $this->actingAs($user)
            ->postJson("/positions/{$position->id}/restore");

        $response->assertOk();

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_assign_position_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $holderUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $startedAt = '2026-04-01 00:00:00';

        $response = $this->actingAs($user)
            ->postJson("/positions/{$position->id}/holder", [
                'user_id' => $holderUser->id,
                'started_at' => $startedAt,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.position_id', $position->id)
            ->assertJsonPath('data.user_id', $holderUser->id);

        $this->assertDatabaseHas('position_holders', [
            'position_id' => $position->id,
            'user_id' => $holderUser->id,
        ]);
    }

    public function test_can_get_active_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $holderUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $holderUser->id,
            'started_at' => Carbon::parse('2026-04-01'),
            'ended_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/positions/{$position->id}/holder");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $holder->id)
            ->assertJsonPath('data.user_id', $holderUser->id);
    }

    public function test_active_holder_returns_null_when_position_has_no_active_holder(): void
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

        $response = $this->actingAs($user)
            ->getJson("/positions/{$position->id}/holder");

        $response
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_can_get_holder_history(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $holderUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $holderUser->id,
            'started_at' => Carbon::parse('2026-01-01'),
            'ended_at' => Carbon::parse('2026-04-01'),
        ]);

        PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $holderUser->id,
            'started_at' => Carbon::parse('2026-04-01'),
            'ended_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/positions/{$position->id}/holders");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_end_active_holder(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $holderUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $position = Position::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => PositionStatus::ACTIVE,
        ]);

        $this->assertNotNull($position->id);
        $this->assertIsString($position->id);

        $this->assertSame(
            $position->id,
            (string) $position->getKey()
        );

        $holder = PositionHolder::factory()->create([
            'position_id' => $position->id,
            'user_id' => $holderUser->id,
            'started_at' => Carbon::parse('2026-01-01'),
            'ended_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/positions/{$position->id}/holder/end", [
                'ended_at' => '2026-04-01',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $holder->id);

        $this->assertDatabaseHas('position_holders', [
            'id' => $holder->id,
            'ended_at' => '2026-04-01 00:00:00',
        ]);
    }

    public function test_end_holder_returns_not_found_when_position_has_no_active_holder(): void
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

        $response = $this->actingAs($user)
            ->postJson("/positions/{$position->id}/holder/end", [
                'ended_at' => '2026-04-01',
            ]);

        $response
            ->assertNotFound()
            ->assertJson([
                'message' => 'Position has no active holder.',
            ]);
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

        $this->actingAs($user)
            ->getJson("/positions/{$position->id}")
            ->assertForbidden();
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

        $this->actingAs($user)
            ->putJson("/positions/{$position->id}", [
                'name' => 'Unauthorized Position',
            ])
            ->assertForbidden();
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

        $this->actingAs($user)
            ->deleteJson("/positions/{$position->id}")
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_view_positions(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::INACTIVE,
        ]);

        $this->actingAs($user)
            ->getJson('/positions')
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_create_position(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER,
            'status' => UserStatus::INACTIVE,
        ]);

        $this->actingAs($user)
            ->postJson('/positions', [
                'code' => 'POS-001',
                'name' => 'Inactive User Position',
                'status' => PositionStatus::ACTIVE->value,
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_view_position_from_different_tenant(): void
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

        $this->actingAs($admin)
            ->getJson("/positions/{$position->id}")
            ->assertOk();
    }
}
