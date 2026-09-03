<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Livewire\Population\Families;
use App\Models\Family;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FamiliesCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_with_population_manage_can_create_family(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();

        Livewire::actingAs($user)
            ->test(Families::class)
            ->call('create')
            ->set('no_kk', '6271011234567890')
            ->set('alamat', 'Jl. Contoh No. 1')
            ->set('rt', '001')
            ->set('rw', '002')
            ->set('kelurahan', 'Pahandut')
            ->set('kecamatan', 'Pahandut')
            ->set('kabupaten_kota', 'Palangka Raya')
            ->set('provinsi', 'Kalimantan Tengah')
            ->set('kode_pos', '73111')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('families', [
            'tenant_id' => $tenant->id,
            'no_kk' => '6271011234567890',
            'alamat' => 'Jl. Contoh No. 1',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
    }

    public function test_tenant_user_without_population_manage_cannot_create_family(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        Livewire::actingAs($user)
            ->test(Families::class)
            ->call('save')
            ->assertStatus(403);

        $this->assertDatabaseCount('families', 0);
    }
}
