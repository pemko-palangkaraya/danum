<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Models\Citizen;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopulationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_export_citizens_as_csv(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        Citizen::factory()->forTenant($tenant)->create(['nik' => '6271010101010101', 'nama_lengkap' => 'Warga Export']);

        $response = $this->actingAs($user)->get(route('population.citizens.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertDownload();
        $this->assertStringContainsString('6271010101010101', $response->streamedContent());
        $this->assertStringContainsString('Warga Export', $response->streamedContent());
    }

    public function test_super_admin_export_requires_and_uses_selected_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->superAdmin()->create();

        Citizen::factory()->forTenant($tenant)->create(['nik' => '6271010101010101', 'nama_lengkap' => 'Warga Tenant A']);
        Citizen::factory()->forTenant($otherTenant)->create(['nik' => '6271010101010102', 'nama_lengkap' => 'Warga Tenant B']);

        $this->actingAs($user)
            ->get(route('population.citizens.export', ['format' => 'csv']))
            ->assertStatus(422);

        $response = $this->actingAs($user)->get(route('population.citizens.export', [
            'format' => 'csv',
            'tenant_id' => $tenant->id,
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('6271010101010101', $content);
        $this->assertStringContainsString('Warga Tenant A', $content);
        $this->assertStringNotContainsString('6271010101010102', $content);
        $this->assertStringNotContainsString('Warga Tenant B', $content);
    }
}
