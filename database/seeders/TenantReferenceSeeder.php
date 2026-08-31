<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantCategory;
use Illuminate\Database\Seeder;

class TenantReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['pemerintah-pusat', 'Pemerintah Pusat'],
            ['pemerintah-provinsi', 'Pemerintah Provinsi'],
            ['pemerintah-kabupaten', 'Pemerintah Kabupaten'],
            ['pemerintah-kota', 'Pemerintah Kota'],
            ['sekretariat-daerah', 'Sekretariat Daerah'],
            ['sekretariat-dprd', 'Sekretariat DPRD'],
            ['inspektorat', 'Inspektorat'],
            ['dinas', 'Dinas'],
            ['badan', 'Badan'],
            ['satpol-pp', 'Satuan Polisi Pamong Praja'],
            ['kecamatan', 'Kecamatan'],
            ['kelurahan', 'Kelurahan'],
            ['desa', 'Pemerintahan Desa'],
            ['uptd', 'UPTD'],
            ['puskesmas', 'Puskesmas'],
            ['rumah-sakit-daerah', 'Rumah Sakit Daerah'],
            ['blud', 'Badan Layanan Umum Daerah (BLUD)'],
            ['satuan-pendidikan', 'Satuan Pendidikan'],
            ['bumn', 'Badan Usaha Milik Negara (BUMN)'],
            ['bumd', 'Badan Usaha Milik Daerah (BUMD)'],
            ['lainnya', 'Lainnya'],
        ];

        foreach ($categories as $index => [$code, $name]) {
            TenantCategory::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true],
            );
        }

        $category = fn (string $code) => TenantCategory::where('code', $code)->value('id');

        $tenants = [
            // Pemerintah pusat
            ['pemerintah-indonesia', 'Pemerintah Republik Indonesia', 'pemerintah-pusat', 'DKI Jakarta', 'Jakarta Pusat', 'Gambir', 'Gambir', 'Presiden Republik Indonesia', 'Presiden'],

            // Pemerintah provinsi
            ['pemprov-kalimantan-tengah', 'Pemerintah Provinsi Kalimantan Tengah', 'pemerintah-provinsi', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Gubernur Kalimantan Tengah', 'Gubernur'],
            ['pemprov-dki-jakarta', 'Pemerintah Provinsi DKI Jakarta', 'pemerintah-provinsi', 'DKI Jakarta', 'Jakarta Pusat', 'Gambir', 'Gambir', 'Gubernur DKI Jakarta', 'Gubernur'],

            // Kabupaten
            ['pemkab-kapuas', 'Pemerintah Kabupaten Kapuas', 'pemerintah-kabupaten', 'Kalimantan Tengah', 'Kuala Kapuas', 'Selat', 'Selat Hilir', 'Bupati Kapuas', 'Bupati'],
            ['pemkab-kotawaringin-timur', 'Pemerintah Kabupaten Kotawaringin Timur', 'pemerintah-kabupaten', 'Kalimantan Tengah', 'Sampit', 'Mentawa Baru Ketapang', 'Mentawa Baru Hulu', 'Bupati Kotawaringin Timur', 'Bupati'],
            ['pemkab-banjar', 'Pemerintah Kabupaten Banjar', 'pemerintah-kabupaten', 'Kalimantan Selatan', 'Martapura', 'Martapura', 'Jawa', 'Bupati Banjar', 'Bupati'],

            // Kota
            ['pemkot-palangka-raya', 'Pemerintah Kota Palangka Raya', 'pemerintah-kota', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Wali Kota Palangka Raya', 'Wali Kota'],
            ['pemkot-banjarmasin', 'Pemerintah Kota Banjarmasin', 'pemerintah-kota', 'Kalimantan Selatan', 'Banjarmasin', 'Banjarmasin Tengah', 'Kertak Baru Ulu', 'Wali Kota Banjarmasin', 'Wali Kota'],
            ['pemkot-surakarta', 'Pemerintah Kota Surakarta', 'pemerintah-kota', 'Jawa Tengah', 'Surakarta', 'Banjarsari', 'Manahan', 'Wali Kota Surakarta', 'Wali Kota'],

            // Perangkat daerah
            ['setda-palangka-raya', 'Sekretariat Daerah Kota Palangka Raya', 'sekretariat-daerah', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Sekretaris Daerah Kota Palangka Raya', 'Sekretaris Daerah'],
            ['dprd-palangka-raya', 'Sekretariat DPRD Kota Palangka Raya', 'sekretariat-dprd', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Sekretaris DPRD Kota Palangka Raya', 'Sekretaris DPRD'],
            ['inspektorat-palangka-raya', 'Inspektorat Kota Palangka Raya', 'inspektorat', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Inspektur Kota Palangka Raya', 'Inspektur'],
            ['dinkes-palangka-raya', 'Dinas Kesehatan Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Kesehatan', 'Kepala Dinas'],
            ['disdukcapil-palangka-raya', 'Dinas Kependudukan dan Pencatatan Sipil Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Dukcapil', 'Kepala Dinas'],
            ['bappeda-palangka-raya', 'Badan Perencanaan Pembangunan Daerah Kota Palangka Raya', 'badan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Bappeda', 'Kepala Badan'],
            ['satpol-pp-palangka-raya', 'Satuan Polisi Pamong Praja Kota Palangka Raya', 'satpol-pp', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Kepala Satpol PP', 'Kepala Satuan'],

            // Kecamatan, kelurahan, desa
            ['kecamatan-pahandut', 'Kecamatan Pahandut', 'kecamatan', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Camat Pahandut', 'Camat'],
            ['kecamatan-jekan-raya', 'Kecamatan Jekan Raya', 'kecamatan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Camat Jekan Raya', 'Camat'],
            ['kelurahan-langkai', 'Kelurahan Langkai', 'kelurahan', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Lurah Langkai', 'Lurah'],
            ['kelurahan-menteng', 'Kelurahan Menteng', 'kelurahan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Lurah Menteng', 'Lurah'],
            ['desa-tumbang-talang', 'Pemerintah Desa Tumbang Talang', 'desa', 'Kalimantan Tengah', 'Gunung Mas', 'Kurun', 'Tumbang Talang', 'Kepala Desa Tumbang Talang', 'Kepala Desa'],
            ['desa-bukit-rawi', 'Pemerintah Desa Bukit Rawi', 'desa', 'Kalimantan Tengah', 'Pulang Pisau', 'Kahayan Tengah', 'Bukit Rawi', 'Kepala Desa Bukit Rawi', 'Kepala Desa'],

            // Unit layanan
            ['rsud-doris-sylvanus', 'RSUD dr. Doris Sylvanus', 'rumah-sakit-daerah', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Palangka', 'Direktur RSUD dr. Doris Sylvanus', 'Direktur'],
            ['puskesmas-pahandut', 'UPT Puskesmas Pahandut', 'puskesmas', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Pahandut', 'Kepala Puskesmas Pahandut', 'Kepala Puskesmas'],
            ['smkn-1-palangka-raya', 'SMK Negeri 1 Palangka Raya', 'satuan-pendidikan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Bukit Tunggal', 'Kepala SMK Negeri 1 Palangka Raya', 'Kepala Sekolah'],

            // BUMN/BUMD sebagai contoh organisasi non-pemerintahan daerah
            ['bumn-contoh', 'BUMN Contoh Indonesia', 'bumn', 'DKI Jakarta', 'Jakarta Pusat', 'Gambir', 'Gambir', 'Direktur Utama', 'Direktur Utama'],
            ['bumd-palangka-raya', 'BUMD Kota Palangka Raya', 'bumd', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Direktur Utama', 'Direktur Utama'],
        ];

        foreach ($tenants as [$code, $name, $categoryCode, $province, $city, $district, $village, $headName, $headTitle]) {
            Tenant::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'tenant_category_id' => $category($categoryCode),
                    'province' => $province,
                    'city' => $city,
                    'district' => $district,
                    'village' => $village,
                    'address' => 'Alamat contoh untuk data master',
                    'phone' => null,
                    'email' => null,
                    'logo' => null,
                    'letterhead_path' => null,
                    'head_name' => $headName,
                    'head_title' => $headTitle,
                    'status' => TenantStatus::ACTIVE,
                ],
            );
        }
    }
}
