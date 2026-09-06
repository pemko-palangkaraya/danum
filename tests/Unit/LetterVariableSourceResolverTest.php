<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use App\Services\LetterVariableSourceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterVariableSourceResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_variables_are_resolved_from_real_kk_relationships(): void
    {
        $tenant = Tenant::factory()->create();
        $head = Citizen::factory()->forTenant($tenant)->male()->create(['status_perkawinan' => 'married']);
        $spouse = Citizen::factory()->forTenant($tenant)->female()->create(['status_perkawinan' => 'married']);
        $child = Citizen::factory()->forTenant($tenant)->create(['status_perkawinan' => 'single']);

        $family = Family::factory()->forTenant($tenant)->create(['head_citizen_id' => $head->id]);

        FamilyMember::factory()->forFamily($family)->forCitizen($head)->relation('head')->create(['urutan' => 1]);
        FamilyMember::factory()->forFamily($family)->forCitizen($spouse)->relation('spouse')->create(['urutan' => 2]);
        FamilyMember::factory()->forFamily($family)->forCitizen($child)->relation('child')->create(['urutan' => 3]);

        $resolver = app(LetterVariableSourceResolver::class);

        $headData = $resolver->family($head);
        $this->assertSame($spouse->nama_lengkap, $headData['values']['nama_pasangan']);
        $this->assertSame($child->nama_lengkap, $headData['children'][0]['nama']);

        $spouseData = $resolver->family($spouse);
        $this->assertSame($head->nama_lengkap, $spouseData['values']['nama_pasangan']);
        $this->assertSame($child->nama_lengkap, $spouseData['children'][0]['nama']);

        $childData = $resolver->family($child);
        $this->assertSame('-', $childData['values']['nama_pasangan']);
        $this->assertSame('-', $childData['children'][0]['nama']);
    }
}
