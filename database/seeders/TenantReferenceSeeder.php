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
            ['upt', 'Unit Pelaksana Teknis'],
            ['uptd', 'Unit Pelaksana Teknis Daerah'],
            ['rumah-sakit-daerah', 'Rumah Sakit Daerah'],
            ['puskesmas', 'Puskesmas / Fasilitas Pelayanan Kesehatan'],
            ['satuan-pendidikan', 'Satuan Pendidikan'],
            ['perguruan-tinggi', 'Perguruan Tinggi'],
            ['blud', 'Badan Layanan Umum Daerah (BLUD)'],
            ['bumd', 'Badan Usaha Milik Daerah (BUMD)'],
            ['lainnya', 'Lainnya'],
        ];

        foreach ($categories as $index => [$code, $name]) {
            TenantCategory::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true],
            );
        }

        $tenants = [
            ['SETDA', 'Sekretariat Daerah Kota Palangka Raya', 'sekretariat-daerah', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Sekretaris Daerah', 'Sekretaris Daerah'],
            ['DPRD', 'Sekretariat DPRD Kota Palangka Raya', 'sekretariat-dprd', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Sekretaris DPRD', 'Sekretaris DPRD'],
            ['INSPEK', 'Inspektorat Kota Palangka Raya', 'inspektorat', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Inspektur', 'Inspektur'],
            ['DINKES', 'Dinas Kesehatan Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Kesehatan', 'Kepala Dinas'],
            ['DISDIK', 'Dinas Pendidikan Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Pendidikan', 'Kepala Dinas'],
            ['DUKCAPIL', 'Dinas Kependudukan dan Pencatatan Sipil Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Dukcapil', 'Kepala Dinas'],
            ['DPMPTSP', 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala DPMPTSP', 'Kepala Dinas'],
            ['BAPPEDA', 'Badan Perencanaan Pembangunan Daerah Kota Palangka Raya', 'badan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Bappeda', 'Kepala Badan'],
            ['BPKAD', 'Badan Pengelola Keuangan dan Aset Daerah Kota Palangka Raya', 'badan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala BPKAD', 'Kepala Badan'],
            ['SATPOLPP', 'Satuan Polisi Pamong Praja Kota Palangka Raya', 'satpol-pp', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Kepala Satpol PP', 'Kepala Satuan'],
            ['RSUD', 'RSUD dr. Doris Sylvanus', 'rumah-sakit-daerah', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Palangka', 'Direktur RSUD dr. Doris Sylvanus', 'Direktur'],
            ['PKM-PHD', 'UPT Puskesmas Pahandut', 'puskesmas', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Pahandut', 'Kepala Puskesmas Pahandut', 'Kepala Puskesmas'],
            ['SMKN1', 'SMK Negeri 1 Palangka Raya', 'satuan-pendidikan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Bukit Tunggal', 'Kepala SMK Negeri 1 Palangka Raya', 'Kepala Sekolah'],
            ['UPR', 'Universitas Palangka Raya', 'perguruan-tinggi', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Palangka', 'Rektor Universitas Palangka Raya', 'Rektor'],
            ['BUMD', 'BUMD Kota Palangka Raya', 'bumd', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Direktur Utama', 'Direktur Utama'],
        ];

        foreach ($tenants as [$code, $name, $categoryCode, $province, $city, $district, $village, $headName, $headTitle]) {
            Tenant::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'tenant_category_id' => TenantCategory::where('code', $categoryCode)->value('id'),
                    'province' => $province,
                    'city' => $city,
                    'district' => $district,
                    'village' => $village,
                    'address' => 'Alamat contoh data master',
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
