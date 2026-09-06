<?php

declare(strict_types=1);

namespace Tests\Feature\Population;

use App\Models\Tenant;
use App\Models\User;
use App\Services\CitizenImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CitizenImportReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_accepts_reference_labels_and_stores_canonical_codes(): void
    {
        DB::table('population_reference_data')->insert([
            ['group' => 'gender', 'code' => 'male', 'label' => 'Laki-laki', 'sort_order' => 1, 'is_active' => true],
            ['group' => 'blood_type', 'code' => 'O', 'label' => 'O', 'sort_order' => 1, 'is_active' => true],
            ['group' => 'religion', 'code' => 'buddhist', 'label' => 'Buddha', 'sort_order' => 1, 'is_active' => true],
            ['group' => 'marital_status', 'code' => 'single', 'label' => 'Belum Kawin', 'sort_order' => 1, 'is_active' => true],
            ['group' => 'citizenship', 'code' => 'WNI', 'label' => 'WNI', 'sort_order' => 1, 'is_active' => true],
            ['group' => 'population_status', 'code' => 'active', 'label' => 'Aktif', 'sort_order' => 1, 'is_active' => true],
        ]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantAdmin($tenant)->create();
        $file = UploadedFile::fake()->createWithContent(
            'citizens.csv',
            "NIK;Nama Lengkap;Jenis Kelamin;Golongan Darah;Agama;Status Perkawinan;Kewarganegaraan;Status Kependudukan\n6271010101010101;Warga Referensi;Laki-laki;O;Buddha;Belum Kawin;WNI;Aktif\n",
        );

        $service = app(CitizenImportService::class);
        $preview = $service->preview($file, $tenant->id, 'skip');

        $this->assertSame(1, $preview['validCount']);
        $this->assertSame(0, $preview['invalidCount']);

        $this->assertSame(1, $service->import($preview['rows'], $tenant->id, 'skip', $user->id));

        $this->assertDatabaseHas('citizens', [
            'tenant_id' => $tenant->id,
            'nik' => '6271010101010101',
            'jenis_kelamin' => 'male',
            'golongan_darah' => 'O',
            'agama' => 'buddhist',
            'status_perkawinan' => 'single',
            'kewarganegaraan' => 'WNI',
            'status_kependudukan' => 'active',
        ]);
    }
}
