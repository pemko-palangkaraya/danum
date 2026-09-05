<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CitizenImportService;
use App\Services\CitizenService;
use App\Services\FamilyService;
use App\Services\PopulationLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PopulationAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_create_and_update_are_audited(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        Auth::login($user);

        $service = app(CitizenService::class);
        $data = [
            'nik' => '6271010101010001',
            'nama_lengkap' => 'Warga Audit',
            'kewarganegaraan' => 'WNI',
            'status_kependudukan' => 'active',
        ];

        $citizen = $service->save($tenant->id, $data, null, $user->id);
        $service->save($tenant->id, [...$data, 'nama_lengkap' => 'Warga Audit Updated'], $citizen->id, $user->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'population.citizen.created',
            'auditable_type' => Citizen::class,
            'auditable_id' => $citizen->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'population.citizen.updated',
            'auditable_type' => Citizen::class,
            'auditable_id' => $citizen->id,
            'tenant_id' => $tenant->id,
        ]);

        $updateLog = AuditLog::query()
            ->where('action', 'population.citizen.updated')
            ->where('auditable_id', $citizen->id)
            ->firstOrFail();

        $this->assertSame('Warga Audit', $updateLog->old_values['nama_lengkap']);
        $this->assertSame('Warga Audit Updated', $updateLog->new_values['nama_lengkap']);
    }

    public function test_family_create_and_update_are_audited(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        Auth::login($user);

        $mock = $this->mock(PopulationLocationService::class);
        $mock->shouldReceive('existsForTenant')->andReturnTrue();
        $this->app->instance(PopulationLocationService::class, $mock);

        $service = app(FamilyService::class);
        $data = [
            'no_kk' => '6271010101010001',
            'head_citizen_id' => '',
            'alamat' => 'Jl. Audit',
            'rt' => '001',
            'rw' => '002',
            'kelurahan' => 'Pahandut',
            'kecamatan' => 'Pahandut',
            'kabupaten_kota' => 'Palangka Raya',
            'provinsi' => 'Kalimantan Tengah',
            'kode_pos' => '73111',
            'status' => 'active',
        ];

        $family = $service->save($tenant->id, $data, null, $user->id);
        $service->save($tenant->id, [...$data, 'alamat' => 'Jl. Audit Updated'], $family->id, $user->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'population.family.created',
            'auditable_type' => Family::class,
            'auditable_id' => $family->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'population.family.updated',
            'auditable_type' => Family::class,
            'auditable_id' => $family->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_family_member_create_update_and_delete_are_audited(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        Auth::login($user);

        $family = Family::factory()->forTenant($tenant)->create();
        $citizen = Citizen::factory()->forTenant($tenant)->create();
        $service = app(FamilyService::class);

        $service->addMember($tenant->id, $family->id, $citizen->id, 'Anak');
        $service->addMember($tenant->id, $family->id, $citizen->id, 'Istri');
        $service->removeMember($tenant->id, $family->id, $citizen->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'population.family_member.created',
            'auditable_type' => FamilyMember::class,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'population.family_member.updated',
            'auditable_type' => FamilyMember::class,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'population.family_member.deleted',
            'auditable_type' => FamilyMember::class,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_citizen_import_is_audited_as_one_transaction(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        Auth::login($user);

        $service = app(CitizenImportService::class);
        $count = $service->import([
            [
                'nik' => '6271010101010002',
                'nama_lengkap' => 'Import Satu',
                'kewarganegaraan' => 'WNI',
                'status_kependudukan' => 'active',
            ],
            [
                'nik' => '6271010101010003',
                'nama_lengkap' => 'Import Dua',
                'kewarganegaraan' => 'WNI',
                'status_kependudukan' => 'active',
            ],
        ], $tenant->id, 'skip', $user->id);

        $this->assertSame(2, $count);

        $log = AuditLog::query()
            ->where('action', 'population.citizens.imported')
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $this->assertNull($log->auditable_type);
        $this->assertSame(2, $log->new_values['created_count']);
        $this->assertSame(0, $log->new_values['updated_count']);
        $this->assertSame(0, $log->new_values['skipped_count']);
        $this->assertSame(2, $log->new_values['total_changed']);
    }
}
