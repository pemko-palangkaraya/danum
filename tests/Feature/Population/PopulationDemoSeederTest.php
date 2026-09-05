<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Database\Seeders\PopulationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PopulationDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_population_stays_inside_demo_tenant_location(): void
    {
        $tenant = Tenant::factory()->create([
            'code' => 'DEMO001',
            'province' => 'Kalimantan Tengah',
            'city' => 'Palangka Raya',
            'district' => 'Rakumpit',
            'village' => 'Mungku Baru',
            'status' => TenantStatus::ACTIVE,
        ]);

        Artisan::call('db:seed', ['--class' => PopulationDemoSeeder::class]);

        $this->assertDatabaseCount('families', 20);
        $this->assertDatabaseHas('families', [
            'tenant_id' => $tenant->id,
            'kelurahan' => 'Mungku Baru',
            'kecamatan' => 'Rakumpit',
            'kabupaten_kota' => 'Palangka Raya',
            'provinsi' => 'Kalimantan Tengah',
            'kode_pos' => '73229',
        ]);

        $this->assertSame(1, DB::table('families')->distinct()->count('kelurahan'));
        $this->assertSame(1, DB::table('families')->distinct()->count('kecamatan'));
        $this->assertSame(1, DB::table('families')->distinct()->count('tenant_id'));
    }
}
