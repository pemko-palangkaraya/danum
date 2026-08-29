<?php

declare(strict_types=1);

namespace TestsFeatureOutgoingLetters;

use App\Enums\OutgoingLetterStatus;
use App\Enums\UserRole;
use App\Models\OutgoingLetter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OutgoingLetterListScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_sees_all_letters_belonging_to_their_tenant_by_default(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->tenantAdmin($tenant)->create();
        $maker = User::factory()->tenantUser($tenant)->create();
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->tenantUser($otherTenant)->create();

        $ownLetter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $maker->id,
            'status' => OutgoingLetterStatus::DRAFT,
            'subject' => 'Surat Milik Tenant',
        ]);
        $secondLetter = OutgoingLetter::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $admin->id,
            'status' => OutgoingLetterStatus::ISSUED,
            'subject' => 'Surat Kedua Tenant',
        ]);
        $foreignLetter = OutgoingLetter::factory()->create([
            'tenant_id' => $otherTenant->id,
            'created_by' => $otherUser->id,
            'status' => OutgoingLetterStatus::ISSUED,
            'subject' => 'Surat Tenant Lain',
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\OutgoingLetters\Index::class)
            ->assertSet('filter', 'all')
            ->assertSee($ownLetter->subject)
            ->assertSee($secondLetter->subject)
            ->assertDontSee($foreignLetter->subject);
    }

    public function test_super_admin_sees_letters_from_all_tenants_by_default(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);

        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $userA = User::factory()->tenantUser($tenantA)->create();
        $userB = User::factory()->tenantUser($tenantB)->create();

        $letterA = OutgoingLetter::factory()->create([
            'tenant_id' => $tenantA->id,
            'created_by' => $userA->id,
            'status' => OutgoingLetterStatus::DRAFT,
            'subject' => 'Surat Tenant A',
        ]);
        $letterB = OutgoingLetter::factory()->create([
            'tenant_id' => $tenantB->id,
            'created_by' => $userB->id,
            'status' => OutgoingLetterStatus::ISSUED,
            'subject' => 'Surat Tenant B',
        ]);

        Livewire::actingAs($superAdmin)
            ->test(\App\Livewire\OutgoingLetters\Index::class)
            ->assertSet('filter', 'all')
            ->assertSee($letterA->subject)
            ->assertSee($letterB->subject);
    }
}
