<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_tenant_profile(): void
    {
        $response = $this->getJson('/api/tenant/profile');

        $response->assertUnauthorized();
    }

    public function test_tenant_user_can_view_own_profile(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Kelurahan Mungku Baru',
        ]);

        $user = User::factory()
            ->for($tenant)
            ->create([
                'role' => UserRole::TENANT_USER,
            ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/api/tenant/profile');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $tenant->id,
            )
            ->assertJsonPath(
                'data.name',
                'Kelurahan Mungku Baru',
            );
    }

    public function test_super_admin_cannot_view_tenant_profile(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'tenant_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/api/tenant/profile');

        $response->assertForbidden();
    }

    public function test_tenant_user_can_update_own_profile(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Nama Lama',
            'address' => 'Alamat Lama',
            'phone' => '0811111111',
        ]);

        $user = User::factory()
            ->for($tenant)
            ->create([
                'role' => UserRole::TENANT_USER,
            ]);

        $response = $this
            ->actingAs($user)
            ->putJson('/api/tenant/profile', [
                'name' => 'Kelurahan Mungku Baru',
                'address' => 'Jalan Mungku Baru',
                'phone' => '08123456789',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.name',
                'Kelurahan Mungku Baru',
            )
            ->assertJsonPath(
                'data.address',
                'Jalan Mungku Baru',
            )
            ->assertJsonPath(
                'data.phone',
                '08123456789',
            );

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Kelurahan Mungku Baru',
            'address' => 'Jalan Mungku Baru',
            'phone' => '08123456789',
        ]);
    }

    public function test_tenant_user_can_partially_update_profile(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Nama Lama',
            'address' => 'Alamat Lama',
            'phone' => '0811111111',
        ]);

        $user = User::factory()
            ->for($tenant)
            ->create([
                'role' => UserRole::TENANT_USER,
            ]);

        // $response = $this
        //     ->patchJson('/api/tenant/profile', [
        //         'phone' => '0819999999',
        //     ]);

        $response = $this
            ->actingAs($user)
            ->patchJson('/api/tenant/profile', [
                'phone' => '0819999999',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.phone',
                '0819999999',
            )
            ->assertJsonPath(
                'data.name',
                'Nama Lama',
            );

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Nama Lama',
            'phone' => '0819999999',
        ]);
    }

    public function test_super_admin_cannot_update_tenant_profile(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Nama Lama',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'tenant_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->putJson('/api/tenant/profile', [
                'name' => 'Nama Baru',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Nama Lama',
        ]);
    }

    public function test_profile_cannot_update_system_fields(): void
    {
        $tenant = Tenant::factory()->create([
            'code' => 'MUNGKU-BARU',
            'status' => true,
            'name' => 'Nama Lama',
        ]);

        $user = User::factory()
            ->for($tenant)
            ->create([
                'role' => UserRole::TENANT_USER,
            ]);

        $response = $this
            ->actingAs($user)
            ->putJson('/api/tenant/profile', [
                'name' => 'Nama Baru',
                'code' => 'HACKED',
                'status' => false,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Nama Baru',
            'code' => 'MUNGKU-BARU',
            'status' => true,
        ]);
    }

    public function test_profile_update_validates_email(): void
    {
        $tenant = Tenant::factory()->create();

        $user = User::factory()
            ->for($tenant)
            ->create([
                'role' => UserRole::TENANT_USER,
            ]);

        $response = $this
            ->actingAs($user)
            ->putJson('/api/tenant/profile', [
                'email' => 'not-an-email',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }
}
