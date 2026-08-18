<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProfileValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_cannot_exceed_150_characters(): void
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
                'name' => str_repeat('A', 151),
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_phone_cannot_exceed_30_characters(): void
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
                'phone' => str_repeat('1', 31),
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_email_cannot_exceed_150_characters(): void
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
                'email' => str_repeat('a', 142) . '@example.com',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_nullable_profile_fields_can_be_set_to_null(): void
    {
        $tenant = Tenant::factory()->create([
            'address' => 'Alamat Lama',
            'phone' => '08123456789',
            'email' => 'old@example.com',
            'logo' => 'logo.png',
            'head_name' => 'Nama Lama',
            'head_title' => 'Lurah',
        ]);

        $user = User::factory()
            ->for($tenant)
            ->create([
                'role' => UserRole::TENANT_USER,
            ]);

        $response = $this
            ->actingAs($user)
            ->putJson('/api/tenant/profile', [
                'address' => null,
                'phone' => null,
                'email' => null,
                'logo' => null,
                'head_name' => null,
                'head_title' => null,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'address' => null,
            'phone' => null,
            'email' => null,
            'logo' => null,
            'head_name' => null,
            'head_title' => null,
        ]);
    }
}
