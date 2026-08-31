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
            ['sekretariat-daerah', 'Sekretariat Daerah'],
            ['sekretariat-dprd', 'Sekretariat DPRD'],
            ['inspektorat', 'Inspektorat'],
            ['dinas', 'Dinas'],
            ['badan', 'Badan'],
            ['satuan-polisi-pamong-praja', 'Satuan Polisi Pamong Praja'],
            ['kecamatan', 'Kecamatan'],
            ['kelurahan', 'Kelurahan'],
            ['uptd', 'UPTD'],
            ['unit-pelaksana-teknis', 'Unit Pelaksana Teknis'],
            ['rumah-sakit-daerah', 'Rumah Sakit Daerah'],
            ['puskesmas', 'Puskesmas'],
            ['blud', 'Badan Layanan Umum Daerah (BLUD)'],
            ['desa', 'Pemerintahan Desa'],
            ['bumn', 'Badan Usaha Milik Negara (BUMN)'],
            ['bumd', 'Badan Usaha Milik Daerah (BUMD)'],
            ['pendidikan', 'Satuan Pendidikan'],
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
            ['setda-palangka-raya', 'Pemerintah Kota Palangka Raya - Sekretariat Daerah', 'sekretariat-daerah', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Menteng', 'Wali Kota Palangka Raya', 'Sekretaris Daerah'],
            ['dprd-palangka-raya', 'DPRD Kota Palangka Raya - Sekretariat DPRD', 'sekretariat-dprd', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Ketua DPRD Kota Palangka Raya', 'Sekretaris DPRD'],
            ['inspektorat-palangka-raya', 'Inspektorat Kota Palangka Raya', 'inspektorat', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Inspektur Kota Palangka Raya', 'Inspektur'],
            ['dinkes-palangka-raya', 'Dinas Kesehatan Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Kesehatan', 'Kepala Dinas'],
            ['disdik-palangka-raya', 'Dinas Pendidikan Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Pendidikan', 'Kepala Dinas'],
            ['disdukcapil-palangka-raya', 'Dinas Kependudukan dan Pencatatan Sipil Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Dukcapil', 'Kepala Dinas'],
            ['dpmptsp-palangka-raya', 'DPMPTSP Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala DPMPTSP', 'Kepala Dinas'],
            ['dinsos-palangka-raya', 'Dinas Sosial Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Sosial', 'Kepala Dinas'],
            ['bappeda-palangka-raya', 'Bappeda Kota Palangka Raya', 'badan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Bappeda', 'Kepala Badan'],
            ['bpkad-palangka-raya', 'BPKAD Kota Palangka Raya', 'badan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala BPKAD', 'Kepala Badan'],
            ['satpol-pp-palangka-raya', 'Satuan Polisi Pamong Praja Kota Palangka Raya', 'satuan-polisi-pamong-praja', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Kepala Satpol PP', 'Kepala Satuan'],
            ['kecamatan-pahandut', 'Kecamatan Pahandut', 'kecamatan', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Camat Pahandut', 'Camat'],
            ['kecamatan-jekan-raya', 'Kecamatan Jekan Raya', 'kecamatan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Camat Jekan Raya', 'Camat'],
            ['kelurahan-langakai', 'Kelurahan Langkai', 'kelurahan', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Lurah Langkai', 'Lurah'],
            ['rsud-doris-sylvanus', 'RSUD dr. Doris Sylvanus', 'rumah-sakit-daerah', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Palangka', 'Direktur RSUD dr. Doris Sylvanus', 'Direktur'],
            ['puskesmas-pahandut', 'UPT Puskesmas Pahandut', 'puskesmas', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Pahandut', 'Kepala UPT Puskesmas Pahandut', 'Kepala Puskesmas'],
            ['smkn-1-palangka-raya', 'SMK Negeri 1 Palangka Raya', 'pendidikan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Bukit Tunggal', 'Kepala SMK Negeri 1 Palangka Raya', 'Kepala Sekolah'],
            ['desa-contoh', 'Pemerintah Desa Contoh', 'desa', 'Kalimantan Tengah', 'Kotawaringin Timur', 'Baamang', 'Desa Contoh', 'Kepala Desa Contoh', 'Kepala Desa'],
            ['bumn-contoh', 'BUMN Contoh Indonesia', 'bumn', 'DKI Jakarta', 'Jakarta Pusat', 'Gambir', 'Gambir', 'Direktur Utama', 'Direktur Utama'],
            ['bumd-contoh', 'BUMD Contoh Indonesia', 'bumd', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Direktur Utama', 'Direktur Utama'],
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
