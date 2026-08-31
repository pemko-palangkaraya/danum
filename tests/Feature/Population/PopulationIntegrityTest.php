<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use Database\Seeders\PopulationReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopulationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_population_reference_seeder_is_idempotent(): void
    {
        $this->seed(PopulationReferenceSeeder::class);
        $this->seed(PopulationReferenceSeeder::class);

        $this->assertDatabaseHas('population_reference_data', [
            'group' => 'gender',
            'code' => 'male',
            'label' => 'Laki-laki',
        ]);

        $this->assertSame(
            2,
            (int) \DB::table('population_reference_data')->where('group', 'gender')->count(),
        );
    }

    public function test_nik_is_unique_within_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        Citizen::factory()->create([
            'tenant_id' => $tenant->id,
            'nik' => '6271010101010001',
        ]);

        $this->expectException(QueryException::class);

        Citizen::factory()->create([
            'tenant_id' => $tenant->id,
            'nik' => '6271010101010001',
        ]);
    }

    public function test_same_nik_is_allowed_in_different_tenants(): void
    {
        $nik = '6271010101010002';
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        Citizen::factory()->create(['tenant_id' => $tenantA->id, 'nik' => $nik]);
        Citizen::factory()->create(['tenant_id' => $tenantB->id, 'nik' => $nik]);

        $this->assertSame(2, Citizen::query()->where('nik', $nik)->count());
    }

    public function test_citizen_cannot_have_two_active_family_memberships(): void
    {
        $tenant = Tenant::factory()->create();
        $citizen = Citizen::factory()->create(['tenant_id' => $tenant->id]);
        $familyA = Family::factory()->create(['tenant_id' => $tenant->id]);
        $familyB = Family::factory()->create(['tenant_id' => $tenant->id]);

        FamilyMember::factory()->create([
            'family_id' => $familyA->id,
            'citizen_id' => $citizen->id,
            'status' => 'active',
        ]);

        $this->expectException(QueryException::class);

        FamilyMember::factory()->create([
            'family_id' => $familyB->id,
            'citizen_id' => $citizen->id,
            'status' => 'active',
        ]);
    }
}
