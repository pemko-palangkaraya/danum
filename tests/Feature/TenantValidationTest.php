<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_payload_requires_the_required_identity_and_location_fields(): void
    {
        $user = User::factory()->superAdmin()->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/tenants', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
                'name',
                'province',
                'city',
                'district',
                'village',
                'status',
            ]);
    }

    public function test_tenant_code_must_be_unique(): void
    {
        $tenant = Tenant::factory()->create(['code' => 'TNT001']);
        $user = User::factory()->superAdmin()->create();

        $response = $this
            ->actingAs($user)
            ->putJson("/api/tenants/{$tenant->id}", [
                'code' => 'TNT001',
            ]);

        $response->assertOk();

        $otherTenant = Tenant::factory()->create(['code' => 'TNT002']);

        $response = $this
            ->actingAs($user)
            ->putJson("/api/tenants/{$otherTenant->id}", [
                'code' => 'TNT001',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }
}
