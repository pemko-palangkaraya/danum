<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Livewire\Population\Families;
use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
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

    public function test_tenant_user_with_population_manage_can_add_family_member(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        $family = Family::factory()->forTenant($tenant)->create();
        $citizen = Citizen::factory()->forTenant($tenant)->create();

        Livewire::actingAs($user)
            ->test(Families::class)
            ->call('showDetail', $family->id)
            ->call('addMember', $family->id, $citizen->id, 'Anak')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('family_members', [
            'family_id' => $family->id,
            'citizen_id' => $citizen->id,
            'hubungan_dalam_keluarga' => 'Anak',
            'status' => 'active',
        ]);
    }

    public function test_member_candidates_exclude_family_heads_and_active_members(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        $family = Family::factory()->forTenant($tenant)->create();
        $head = Citizen::factory()->forTenant($tenant)->create(['nama_lengkap' => 'Warga Kepala']);
        $otherFamilyHead = Citizen::factory()->forTenant($tenant)->create(['nama_lengkap' => 'Warga Kepala Lain']);
        $activeMember = Citizen::factory()->forTenant($tenant)->create(['nama_lengkap' => 'Warga Terdaftar']);
        $available = Citizen::factory()->forTenant($tenant)->create(['nama_lengkap' => 'Warga Tersedia']);

        $family->update(['head_citizen_id' => $head->id]);
        $otherFamily = Family::factory()->forTenant($tenant)->create(['head_citizen_id' => $otherFamilyHead->id]);
        FamilyMember::factory()->create([
            'family_id' => $otherFamily->id,
            'citizen_id' => $activeMember->id,
            'status' => 'active',
        ]);

        $component = Livewire::actingAs($user)
            ->test(Families::class)
            ->call('showDetail', $family->id)
            ->set('memberSearch', 'Warga');

        $candidates = $component->viewData('memberCandidates');

        $this->assertTrue($candidates->contains('id', $available->id));
        $this->assertFalse($candidates->contains('id', $head->id));
        $this->assertFalse($candidates->contains('id', $otherFamilyHead->id));
        $this->assertFalse($candidates->contains('id', $activeMember->id));
    }

    public function test_cannot_add_head_or_already_active_member_as_family_member(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        $family = Family::factory()->forTenant($tenant)->create();
        $head = Citizen::factory()->forTenant($tenant)->create();
        $family->update(['head_citizen_id' => $head->id]);

        Livewire::actingAs($user)
            ->test(Families::class)
            ->call('showDetail', $family->id)
            ->call('addMember', $family->id, $head->id, 'Anak')
            ->assertHasErrors('hubungan_dalam_keluarga');

        $otherFamily = Family::factory()->forTenant($tenant)->create();
        $activeMember = Citizen::factory()->forTenant($tenant)->create();
        FamilyMember::factory()->create([
            'family_id' => $otherFamily->id,
            'citizen_id' => $activeMember->id,
            'status' => 'active',
        ]);

        Livewire::actingAs($user)
            ->test(Families::class)
            ->call('showDetail', $family->id)
            ->call('addMember', $family->id, $activeMember->id, 'Anak')
            ->assertHasErrors('hubungan_dalam_keluarga');
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
