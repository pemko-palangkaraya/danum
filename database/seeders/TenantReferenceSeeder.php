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
            ['kementerian-koordinator', 'Kementerian Koordinator'],
            ['kementerian', 'Kementerian'],
            ['lembaga-negara', 'Lembaga Negara'],
            ['lpnk', 'Lembaga Pemerintah Nonkementerian'],
            ['lembaga-non-struktural', 'Lembaga Nonstruktural'],
            ['tni', 'Tentara Nasional Indonesia'],
            ['polri', 'Kepolisian Negara Republik Indonesia'],
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
            ['upt', 'Unit Pelaksana Teknis'],
            ['uptd', 'Unit Pelaksana Teknis Daerah'],
            ['rumah-sakit-daerah', 'Rumah Sakit Daerah'],
            ['puskesmas', 'Puskesmas / Fasilitas Pelayanan Kesehatan'],
            ['satuan-pendidikan', 'Satuan Pendidikan'],
            ['perguruan-tinggi', 'Perguruan Tinggi'],
            ['blud', 'Badan Layanan Umum Daerah (BLUD)'],
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

        $category = fn (string $code): int => (int) TenantCategory::where('code', $code)->value('id');
        $tenants = [];

        // Seluruh 49 kementerian berdasarkan Perpres 90 Tahun 2025.
        $ministries = [
            ['kemenko-polkam', 'Kementerian Koordinator Bidang Politik dan Keamanan', 'kementerian-koordinator'],
            ['kemenko-kumham-imipas', 'Kementerian Koordinator Bidang Hukum, Hak Asasi Manusia, Imigrasi, dan Pemasyarakatan', 'kementerian-koordinator'],
            ['kemenko-ekonomi', 'Kementerian Koordinator Bidang Perekonomian', 'kementerian-koordinator'],
            ['kemenko-pmk', 'Kementerian Koordinator Bidang Pembangunan Manusia dan Kebudayaan', 'kementerian-koordinator'],
            ['kemenko-infra-bangwil', 'Kementerian Koordinator Bidang Infrastruktur dan Pembangunan Kewilayahan', 'kementerian-koordinator'],
            ['kemenko-pemberdayaan-masyarakat', 'Kementerian Koordinator Bidang Pemberdayaan Masyarakat', 'kementerian-koordinator'],
            ['kemenko-pangan', 'Kementerian Koordinator Bidang Pangan', 'kementerian-koordinator'],
            ['kemensetneg', 'Kementerian Sekretariat Negara', 'kementerian'],
            ['kemendagri', 'Kementerian Dalam Negeri', 'kementerian'],
            ['kemlu', 'Kementerian Luar Negeri', 'kementerian'],
            ['kemhan', 'Kementerian Pertahanan', 'kementerian'],
            ['kemenag', 'Kementerian Agama', 'kementerian'],
            ['kemenhaji-umrah', 'Kementerian Haji dan Umrah', 'kementerian'],
            ['kemenkum', 'Kementerian Hukum', 'kementerian'],
            ['kemenham', 'Kementerian Hak Asasi Manusia', 'kementerian'],
            ['kemenimipas', 'Kementerian Imigrasi dan Pemasyarakatan', 'kementerian'],
            ['kemenkeu', 'Kementerian Keuangan', 'kementerian'],
            ['kemendikdasmen', 'Kementerian Pendidikan Dasar dan Menengah', 'kementerian'],
            ['kemdiktisaintek', 'Kementerian Pendidikan Tinggi, Sains, dan Teknologi', 'kementerian'],
            ['kebudayaan', 'Kementerian Kebudayaan', 'kementerian'],
            ['kemenkes', 'Kementerian Kesehatan', 'kementerian'],
            ['kemensos', 'Kementerian Sosial', 'kementerian'],
            ['kemnaker', 'Kementerian Ketenagakerjaan', 'kementerian'],
            ['kemen-p2mi', 'Kementerian Pelindungan Pekerja Migran Indonesia / BP2MI', 'kementerian'],
            ['kemenperin', 'Kementerian Perindustrian', 'kementerian'],
            ['kemendag', 'Kementerian Perdagangan', 'kementerian'],
            ['esdm', 'Kementerian Energi dan Sumber Daya Mineral', 'kementerian'],
            ['kemenpu', 'Kementerian Pekerjaan Umum', 'kementerian'],
            ['kemen-pkp', 'Kementerian Perumahan dan Kawasan Permukiman', 'kementerian'],
            ['kemendes-pdt', 'Kementerian Desa dan Pembangunan Daerah Tertinggal', 'kementerian'],
            ['kementrans', 'Kementerian Transmigrasi', 'kementerian'],
            ['kemenhub', 'Kementerian Perhubungan', 'kementerian'],
            ['komdigi', 'Kementerian Komunikasi dan Digital', 'kementerian'],
            ['kementan', 'Kementerian Pertanian', 'kementerian'],
            ['kemhut', 'Kementerian Kehutanan', 'kementerian'],
            ['kkp', 'Kementerian Kelautan dan Perikanan', 'kementerian'],
            ['atr-bpn', 'Kementerian Agraria dan Tata Ruang / Badan Pertanahan Nasional', 'kementerian'],
            ['bappenas', 'Kementerian PPN / Badan Perencanaan Pembangunan Nasional', 'kementerian'],
            ['panrb', 'Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi', 'kementerian'],
            ['kementerian-bumn', 'Kementerian Badan Usaha Milik Negara', 'kementerian'],
            ['kependudukan-bangga', 'Kementerian Kependudukan dan Pembangunan Keluarga / BKKBN', 'kementerian'],
            ['klh-bplh', 'Kementerian Lingkungan Hidup / Badan Pengendalian Lingkungan Hidup', 'kementerian'],
            ['investasi-hilirisasi-bkpm', 'Kementerian Investasi dan Hilirisasi / BKPM', 'kementerian'],
            ['kemenkop', 'Kementerian Koperasi', 'kementerian'],
            ['kemen-umkm', 'Kementerian Usaha Mikro, Kecil, dan Menengah', 'kementerian'],
            ['kemenpar', 'Kementerian Pariwisata', 'kementerian'],
            ['ekraf-bekraf', 'Kementerian Ekonomi Kreatif / Badan Ekonomi Kreatif', 'kementerian'],
            ['kemen-pppa', 'Kementerian Pemberdayaan Perempuan dan Perlindungan Anak', 'kementerian'],
            ['kemenpora', 'Kementerian Pemuda dan Olahraga', 'kementerian'],
        ];

        foreach ($ministries as [$code, $name, $category]) {
            $tenants[] = [$code, $name, $category, 'DKI Jakarta', 'Jakarta Pusat', 'Gambir', 'Gambir', 'Pimpinan Instansi', 'Menteri / Kepala Instansi'];
        }

        // Lembaga negara, aparat, LPNK, dan lembaga publik pusat.
        $centralInstitutions = [
            ['dpr-ri', 'Dewan Perwakilan Rakyat Republik Indonesia', 'lembaga-negara'],
            ['dpd-ri', 'Dewan Perwakilan Daerah Republik Indonesia', 'lembaga-negara'],
            ['mpr-ri', 'Majelis Permusyawaratan Rakyat Republik Indonesia', 'lembaga-negara'],
            ['mahkamah-agung', 'Mahkamah Agung Republik Indonesia', 'lembaga-negara'],
            ['mahkamah-konstitusi', 'Mahkamah Konstitusi Republik Indonesia', 'lembaga-negara'],
            ['komisi-yudisial', 'Komisi Yudisial Republik Indonesia', 'lembaga-negara'],
            ['bpk-ri', 'Badan Pemeriksa Keuangan Republik Indonesia', 'lembaga-negara'],
            ['kejaksaan-agung', 'Kejaksaan Agung Republik Indonesia', 'lembaga-negara'],
            ['tni', 'Tentara Nasional Indonesia', 'tni'],
            ['polri', 'Kepolisian Negara Republik Indonesia', 'polri'],
            ['bkn', 'Badan Kepegawaian Negara', 'lpnk'],
            ['bpkp', 'Badan Pengawasan Keuangan dan Pembangunan', 'lpnk'],
            ['bps', 'Badan Pusat Statistik', 'lpnk'],
            ['bmkg', 'Badan Meteorologi, Klimatologi, dan Geofisika', 'lpnk'],
            ['big', 'Badan Informasi Geospasial', 'lpnk'],
            ['lan', 'Lembaga Administrasi Negara', 'lpnk'],
            ['lkpp', 'Lembaga Kebijakan Pengadaan Barang/Jasa Pemerintah', 'lpnk'],
            ['perpusnas', 'Perpustakaan Nasional Republik Indonesia', 'lpnk'],
            ['badan-pangan-nasional', 'Badan Pangan Nasional', 'lpnk'],
            ['badan-gizi-nasional', 'Badan Gizi Nasional', 'lpnk'],
            ['bnnp', 'Badan Narkotika Nasional', 'lpnk'],
            ['bnpb', 'Badan Nasional Penanggulangan Bencana', 'lpnk'],
            ['basarnas', 'Badan Nasional Pencarian dan Pertolongan', 'lpnk'],
            ['bssn', 'Badan Siber dan Sandi Negara', 'lpnk'],
            ['bpom', 'Badan Pengawas Obat dan Makanan', 'lpnk'],
            ['kpu-ri', 'Komisi Pemilihan Umum Republik Indonesia', 'lembaga-non-struktural'],
            ['bawaslu-ri', 'Badan Pengawas Pemilihan Umum Republik Indonesia', 'lembaga-non-struktural'],
            ['kpk', 'Komisi Pemberantasan Korupsi', 'lembaga-non-struktural'],
            ['bpip', 'Badan Pembinaan Ideologi Pancasila', 'lembaga-non-struktural'],
            ['ppatk', 'Pusat Pelaporan dan Analisis Transaksi Keuangan', 'lembaga-non-struktural'],
            ['komnas-ham', 'Komisi Nasional Hak Asasi Manusia', 'lembaga-non-struktural'],
            ['lpsk', 'Lembaga Perlindungan Saksi dan Korban', 'lembaga-non-struktural'],
        ];

        foreach ($centralInstitutions as [$code, $name, $category]) {
            $tenants[] = [$code, $name, $category, 'DKI Jakarta', 'Jakarta Pusat', 'Gambir', 'Gambir', 'Pimpinan Instansi', 'Ketua / Kepala'];
        }

        // Seluruh level pemerintah daerah + perangkat dan unit layanan.
        $regional = [
            ['pemprov-kalteng', 'Pemerintah Provinsi Kalimantan Tengah', 'pemerintah-provinsi', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Gubernur Kalimantan Tengah', 'Gubernur'],
            ['pemprov-dki', 'Pemerintah Provinsi DKI Jakarta', 'pemerintah-provinsi', 'DKI Jakarta', 'Jakarta Pusat', 'Gambir', 'Gambir', 'Gubernur DKI Jakarta', 'Gubernur'],
            ['pemkab-kapuas', 'Pemerintah Kabupaten Kapuas', 'pemerintah-kabupaten', 'Kalimantan Tengah', 'Kuala Kapuas', 'Selat', 'Selat Hilir', 'Bupati Kapuas', 'Bupati'],
            ['pemkab-kotim', 'Pemerintah Kabupaten Kotawaringin Timur', 'pemerintah-kabupaten', 'Kalimantan Tengah', 'Sampit', 'Mentawa Baru Ketapang', 'Mentawa Baru Hulu', 'Bupati Kotawaringin Timur', 'Bupati'],
            ['pemkab-banjar', 'Pemerintah Kabupaten Banjar', 'pemerintah-kabupaten', 'Kalimantan Selatan', 'Martapura', 'Martapura', 'Jawa', 'Bupati Banjar', 'Bupati'],
            ['pemkot-palangka-raya', 'Pemerintah Kota Palangka Raya', 'pemerintah-kota', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Wali Kota Palangka Raya', 'Wali Kota'],
            ['pemkot-banjarmasin', 'Pemerintah Kota Banjarmasin', 'pemerintah-kota', 'Kalimantan Selatan', 'Banjarmasin', 'Banjarmasin Tengah', 'Kertak Baru Ulu', 'Wali Kota Banjarmasin', 'Wali Kota'],
            ['pemkot-surakarta', 'Pemerintah Kota Surakarta', 'pemerintah-kota', 'Jawa Tengah', 'Surakarta', 'Banjarsari', 'Manahan', 'Wali Kota Surakarta', 'Wali Kota'],
            ['setda-palangka-raya', 'Sekretariat Daerah Kota Palangka Raya', 'sekretariat-daerah', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Sekretaris Daerah', 'Sekretaris Daerah'],
            ['dprd-palangka-raya', 'Sekretariat DPRD Kota Palangka Raya', 'sekretariat-dprd', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Sekretaris DPRD', 'Sekretaris DPRD'],
            ['inspektorat-palangka-raya', 'Inspektorat Kota Palangka Raya', 'inspektorat', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Inspektur', 'Inspektur'],
            ['dinkes-palangka-raya', 'Dinas Kesehatan Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Kesehatan', 'Kepala Dinas'],
            ['disdik-palangka-raya', 'Dinas Pendidikan Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Pendidikan', 'Kepala Dinas'],
            ['disdukcapil-palangka-raya', 'Dinas Kependudukan dan Pencatatan Sipil Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Dinas Dukcapil', 'Kepala Dinas'],
            ['dpmptsp-palangka-raya', 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu Kota Palangka Raya', 'dinas', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala DPMPTSP', 'Kepala Dinas'],
            ['bappeda-palangka-raya', 'Badan Perencanaan Pembangunan Daerah Kota Palangka Raya', 'badan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala Bappeda', 'Kepala Badan'],
            ['bpkad-palangka-raya', 'Badan Pengelola Keuangan dan Aset Daerah Kota Palangka Raya', 'badan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala BPKAD', 'Kepala Badan'],
            ['satpolpp-palangka-raya', 'Satuan Polisi Pamong Praja Kota Palangka Raya', 'satpol-pp', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Kepala Satpol PP', 'Kepala Satuan'],
            ['kecamatan-pahandut', 'Kecamatan Pahandut', 'kecamatan', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Camat Pahandut', 'Camat'],
            ['kecamatan-jekan-raya', 'Kecamatan Jekan Raya', 'kecamatan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Camat Jekan Raya', 'Camat'],
            ['kelurahan-langkai', 'Kelurahan Langkai', 'kelurahan', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Langkai', 'Lurah Langkai', 'Lurah'],
            ['kelurahan-menteng', 'Kelurahan Menteng', 'kelurahan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Lurah Menteng', 'Lurah'],
            ['desa-bukit-rawi', 'Pemerintah Desa Bukit Rawi', 'desa', 'Kalimantan Tengah', 'Pulang Pisau', 'Kahayan Tengah', 'Bukit Rawi', 'Kepala Desa Bukit Rawi', 'Kepala Desa'],
            ['rsud-doris-sylvanus', 'RSUD dr. Doris Sylvanus', 'rumah-sakit-daerah', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Palangka', 'Direktur RSUD dr. Doris Sylvanus', 'Direktur'],
            ['puskesmas-pahandut', 'UPT Puskesmas Pahandut', 'puskesmas', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Pahandut', 'Kepala Puskesmas Pahandut', 'Kepala Puskesmas'],
            ['uptd-contoh', 'UPTD Contoh Kota Palangka Raya', 'uptd', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Kepala UPTD', 'Kepala UPTD'],
            ['smkn-1-palangka-raya', 'SMK Negeri 1 Palangka Raya', 'satuan-pendidikan', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Bukit Tunggal', 'Kepala SMK Negeri 1 Palangka Raya', 'Kepala Sekolah'],
            ['universitas-palangka-raya', 'Universitas Palangka Raya', 'perguruan-tinggi', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Palangka', 'Rektor Universitas Palangka Raya', 'Rektor'],
            ['blud-puskesmas-contoh', 'BLUD Puskesmas Contoh', 'blud', 'Kalimantan Tengah', 'Palangka Raya', 'Pahandut', 'Pahandut', 'Direktur BLUD', 'Direktur'],
            ['bumn-contoh', 'BUMN Contoh Indonesia', 'bumn', 'DKI Jakarta', 'Jakarta Pusat', 'Gambir', 'Gambir', 'Direktur Utama', 'Direktur Utama'],
            ['bumd-palangka-raya', 'BUMD Kota Palangka Raya', 'bumd', 'Kalimantan Tengah', 'Palangka Raya', 'Jekan Raya', 'Menteng', 'Direktur Utama', 'Direktur Utama'],
        ];

        $tenants = [...$tenants, ...$regional];

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
