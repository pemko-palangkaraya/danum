<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\FamilySuccessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilySuccessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_living_spouse_becomes_family_head_and_deceased_member_leaves_active_kk(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();
        $husband = Citizen::factory()->forTenant($tenant)->state([
            'status_perkawinan' => 'married',
        ])->create();
        $wife = Citizen::factory()->forTenant($tenant)->state([
            'status_perkawinan' => 'married',
        ])->create();
        $family = Family::factory()->forTenant($tenant)->create([
            'head_citizen_id' => $husband->id,
        ]);
        $husbandMembership = FamilyMember::factory()->forFamily($family)->forCitizen($husband)->relation('head')->create();

        FamilyMember::factory()->forFamily($family)->forCitizen($wife)->relation('spouse')->create();

        $husband->update(['status_kependudukan' => 'meninggal', 'tanggal_meninggal' => '2026-09-06']);

        app(FamilySuccessionService::class)->handleMemberDeath($husband, '2026-09-06');

        $this->assertDatabaseHas('families', [
            'id' => $family->id,
            'head_citizen_id' => $wife->id,
        ]);
        $this->assertDatabaseHas('citizens', [
            'id' => $wife->id,
            'status_perkawinan' => 'cerai mati',
        ]);
        $this->assertDatabaseHas('family_members', [
            'family_id' => $family->id,
            'citizen_id' => $wife->id,
            'hubungan_dalam_keluarga' => 'head',
            'status' => 'active',
        ]);

        $husbandMembership->refresh();
        $this->assertSame('inactive', $husbandMembership->status);
        $this->assertSame('2026-09-06', $husbandMembership->tanggal_selesai->toDateString());
        $this->assertDatabaseHas('population_events', [
            'family_id' => $family->id,
            'citizen_id' => $wife->id,
            'event_type' => 'family_head_change',
        ]);
    }

    public function test_oldest_living_child_becomes_head_after_both_parents_die(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();
        $father = Citizen::factory()->forTenant($tenant)->state([
            'status_perkawinan' => 'married',
        ])->create();
        $mother = Citizen::factory()->forTenant($tenant)->state([
            'status_perkawinan' => 'married',
        ])->create();
        $oldest = Citizen::factory()->forTenant($tenant)->state([
            'tanggal_lahir' => '1995-01-01',
            'status_perkawinan' => 'single',
        ])->create();
        $younger = Citizen::factory()->forTenant($tenant)->state([
            'tanggal_lahir' => '2000-01-01',
            'status_perkawinan' => 'single',
        ])->create();
        $family = Family::factory()->forTenant($tenant)->create([
            'head_citizen_id' => $father->id,
        ]);
        $fatherMembership = FamilyMember::factory()->forFamily($family)->forCitizen($father)->relation('head')->create();
        $motherMembership = FamilyMember::factory()->forFamily($family)->forCitizen($mother)->relation('spouse')->create();

        FamilyMember::factory()->forFamily($family)->forCitizen($oldest)->relation('child')->create();
        FamilyMember::factory()->forFamily($family)->forCitizen($younger)->relation('child')->create();

        $father->update(['status_kependudukan' => 'meninggal', 'tanggal_meninggal' => '2026-09-01']);
        app(FamilySuccessionService::class)->handleMemberDeath($father, '2026-09-01');

        $mother->refresh()->update(['status_kependudukan' => 'meninggal', 'tanggal_meninggal' => '2026-09-05']);
        app(FamilySuccessionService::class)->handleMemberDeath($mother, '2026-09-05');

        $this->assertDatabaseHas('families', [
            'id' => $family->id,
            'head_citizen_id' => $oldest->id,
        ]);
        $this->assertDatabaseHas('family_members', [
            'family_id' => $family->id,
            'citizen_id' => $oldest->id,
            'hubungan_dalam_keluarga' => 'head',
            'status' => 'active',
        ]);

        $fatherMembership->refresh();
        $motherMembership->refresh();
        $this->assertSame('inactive', $fatherMembership->status);
        $this->assertSame('2026-09-01', $fatherMembership->tanggal_selesai->toDateString());
        $this->assertSame('inactive', $motherMembership->status);
        $this->assertSame('2026-09-05', $motherMembership->tanggal_selesai->toDateString());

        $this->assertDatabaseHas('population_events', [
            'family_id' => $family->id,
            'citizen_id' => $oldest->id,
            'event_type' => 'family_head_change',
        ]);
    }
}
