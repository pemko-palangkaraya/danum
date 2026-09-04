<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Livewire\Population\CitizenImport;
use App\Models\Citizen;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CitizenImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class CitizenImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_population_manage_cannot_open_import_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create();

        Livewire::actingAs($user)
            ->test(CitizenImport::class)
            ->assertStatus(403);

        $this->actingAs($user)
            ->get(route('population.citizens.import'))
            ->assertForbidden();
    }

    public function test_tenant_admin_can_preview_and_import_through_livewire(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        $file = UploadedFile::fake()->createWithContent(
            'citizens.csv',
            "NIK;Nama Lengkap\n6271010101010101;Warga Baru\n",
        );

        Livewire::actingAs($user)
            ->test(CitizenImport::class)
            ->set('file', $file)
            ->assertSet('selectedTenantId', $tenant->id)
            ->assertSet('validCount', 1)
            ->assertSet('invalidCount', 0)
            ->assertSet('ready', true)
            ->call('import')
            ->assertSet('ready', false)
            ->assertSet('rows', [])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('citizens', [
            'tenant_id' => $tenant->id,
            'nik' => '6271010101010101',
            'nama_lengkap' => 'Warga Baru',
            'created_by' => $user->id,
        ]);
    }

    public function test_tenant_user_imports_only_against_their_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();

        Citizen::factory()->forTenant($otherTenant)->create([
            'nik' => '6271010101010101',
        ]);

        $service = app(CitizenImportService::class);
        $file = UploadedFile::fake()->createWithContent(
            'citizens.csv',
            "NIK;Nama Lengkap\n6271010101010101;Warga Tenant Saya\n",
        );

        $preview = $service->preview($file, $tenant->id, 'skip');

        $this->assertSame(1, $preview['validCount']);
        $this->assertSame(0, $preview['invalidCount']);

        $count = $service->import($preview['rows'], $tenant->id, 'skip', $user->id);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('citizens', [
            'tenant_id' => $tenant->id,
            'nik' => '6271010101010101',
            'nama_lengkap' => 'Warga Tenant Saya',
        ]);
    }

    public function test_tenant_user_cannot_change_import_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();

        Livewire::actingAs($user)
            ->test(CitizenImport::class)
            ->set('selectedTenantId', $otherTenant->id)
            ->assertStatus(403);
    }

    public function test_super_admin_can_select_import_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(CitizenImport::class)
            ->assertSet('selectedTenantId', null)
            ->set('selectedTenantId', $tenant->id)
            ->assertSet('selectedTenantId', $tenant->id);
    }

    public function test_preview_marks_existing_nik_as_skipped(): void
    {
        $tenant = Tenant::factory()->create();
        Citizen::factory()->forTenant($tenant)->create([
            'nik' => '6271010101010101',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'citizens.csv',
            "NIK;Nama Lengkap\n6271010101010101;Warga Lama\n",
        );

        $preview = app(CitizenImportService::class)->preview($file, $tenant->id, 'skip');

        $this->assertSame(0, $preview['validCount']);
        $this->assertSame(1, $preview['invalidCount']);
        $this->assertSame('NIK sudah ada — akan dilewati.', $preview['rows'][0]['_error']);
    }

    public function test_import_update_changes_existing_citizen(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        Citizen::factory()->forTenant($tenant)->create([
            'nik' => '6271010101010101',
            'nama_lengkap' => 'Nama Lama',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'citizens.csv',
            "NIK;Nama Lengkap\n6271010101010101;Nama Baru\n",
        );

        $service = app(CitizenImportService::class);
        $preview = $service->preview($file, $tenant->id, 'update');
        $count = $service->import($preview['rows'], $tenant->id, 'update', $user->id);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('citizens', [
            'tenant_id' => $tenant->id,
            'nik' => '6271010101010101',
            'nama_lengkap' => 'Nama Baru',
            'updated_by' => $user->id,
        ]);
    }

    public function test_preview_rejects_duplicate_nik_inside_file(): void
    {
        $tenant = Tenant::factory()->create();
        $file = UploadedFile::fake()->createWithContent(
            'citizens.csv',
            "NIK;Nama Lengkap\n6271010101010101;Warga Satu\n6271010101010101;Warga Dua\n",
        );

        $preview = app(CitizenImportService::class)->preview($file, $tenant->id, 'skip');

        $this->assertSame(0, $preview['validCount']);
        $this->assertSame(2, $preview['invalidCount']);
        $this->assertSame('NIK duplikat di dalam file.', $preview['rows'][0]['_error']);
        $this->assertSame('NIK duplikat di dalam file.', $preview['rows'][1]['_error']);
    }

    public function test_import_revalidates_rows_instead_of_trusting_preview_state(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();

        $rows = [[
            'nik' => '123',
            'nama_lengkap' => 'Data Tidak Valid',
            'tanggal_lahir' => '',
            'jenis_kelamin' => '',
            'golongan_darah' => '',
            'nik_ayah' => '',
            'nik_ibu' => '',
            '_error' => null,
        ]];

        $count = app(CitizenImportService::class)->import($rows, $tenant->id, 'skip', $user->id);

        $this->assertSame(0, $count);
        $this->assertDatabaseMissing('citizens', [
            'tenant_id' => $tenant->id,
            'nik' => '123',
        ]);
    }

    public function test_invalid_duplicate_mode_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $file = UploadedFile::fake()->createWithContent(
            'citizens.csv',
            "NIK;Nama Lengkap\n6271010101010101;Warga\n",
        );

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(CitizenImportService::class)->preview($file, $tenant->id, 'delete');
    }
}
