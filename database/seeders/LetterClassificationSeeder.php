<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LetterClassification;
use Illuminate\Database\Seeder;

class LetterClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $source = 'Permendagri Nomor 83 Tahun 2022';

        // Source order mengikuti urutan entri pada Bagian B lampiran Permendagri.
        // Nilai ini adalah identitas baris sumber, bukan gabungan angka dari kode.
        $classifications = [
            ['000', 'Umum', 'Klasifikasi umum dan administrasi umum.', 6],
            ['100', 'Pemerintahan', 'Klasifikasi urusan pemerintahan.', 228],
            ['200', 'Politik', 'Klasifikasi urusan politik dan hubungan kelembagaan.', 302],
            ['300', 'Keamanan dan Ketertiban', 'Klasifikasi urusan keamanan dan ketertiban.', 384],
            ['400', 'Kesejahteraan Rakyat', 'Klasifikasi urusan kesejahteraan rakyat.', 422],
            ['500', 'Perekonomian', 'Klasifikasi urusan perekonomian.', 1021],
            ['600', 'Pekerjaan Umum dan Ketenagaan', 'Klasifikasi urusan pekerjaan umum dan ketenagaan.', 1940],
            ['700', 'Pengawasan', 'Klasifikasi urusan pengawasan.', 2222],
            ['800', 'Kepegawaian', 'Klasifikasi urusan kepegawaian.', 2238],
            ['900', 'Keuangan', 'Klasifikasi urusan keuangan.', 2350],

            // Klasifikasi umum yang banyak dipakai pada Kecamatan dan Kelurahan.
            ['000.1.5', 'Rapat pimpinan antara lain: Notula/Risalah Rapat', 'Administrasi rapat pimpinan dan notula/risalah rapat.', 17],
            ['000.7.1.4', 'Musrenbang Kecamatan', 'Musyawarah perencanaan pembangunan tingkat kecamatan.', 171],
            ['000.7.1.5', 'Musrenbang Kelurahan', 'Musyawarah perencanaan pembangunan tingkat kelurahan.', 172],
            ['000.7.1.6', 'Musrenbang Desa', 'Musyawarah perencanaan pembangunan tingkat desa.', 173],
            ['100.2.4', 'Fasilitasi Kecamatan', 'Fasilitasi penyelenggaraan pemerintahan dan urusan kecamatan.', 250],
            ['100.2.5', 'Fasilitasi Pelayanan Umum', 'Fasilitasi pelayanan umum kepada masyarakat.', 251],

            // Pemberdayaan masyarakat desa/kelurahan.
            ['400.10.2.1', 'Fasilitasi Pengembangan Desa dan Kelurahan', 'Fasilitasi pengembangan desa dan kelurahan.', 894],
            ['400.10.2.2', 'Administrasi Pemerintahan Desa dan Kelurahan', 'Administrasi pemerintahan desa dan kelurahan.', 895],
            ['400.10.2.3', 'Fasilitasi Permusyawaratan Desa', 'Fasilitasi permusyawaratan desa.', 896],
            ['400.10.2.4', 'Fasilitasi Pengelolaan Keuangan dan Aset Desa', 'Fasilitasi pengelolaan keuangan dan aset desa.', 897],
            ['400.10.2.5', 'Pengembangan Kapasitas Desa', 'Pengembangan kapasitas desa.', 898],

            // Kependudukan yang sering menjadi dasar layanan administrasi warga.
            ['400.12.2.1', 'Identitas Penduduk', 'Administrasi identitas penduduk.', 929],
            ['400.12.2.2', 'Pindah Datang Penduduk Dalam Wilayah NKRI', 'Administrasi perpindahan penduduk dalam wilayah NKRI.', 930],
            ['400.12.2.4', 'Pendataan Penduduk Rentan', 'Pendataan penduduk rentan.', 932],
            ['400.12.3.1', 'Kelahiran dan Kematian', 'Pencatatan kelahiran dan kematian.', 934],
            ['400.12.3.2', 'Perkawinan dan Perceraian', 'Pencatatan perkawinan dan perceraian.', 935],
            ['400.12.4.3', 'Pengelolaan Data Administrasi Kependudukan', 'Pengelolaan data administrasi kependudukan.', 941],
            ['400.12.4.4', 'Penyajian dan Layanan Informasi Administrasi Kependudukan', 'Penyajian dan layanan informasi administrasi kependudukan.', 942],

            // Klasifikasi operasional yang sudah dipakai oleh fitur surat.
            ['400.7.22', 'Surat Keterangan, Sertifikasi dan Perijinan', 'Surat keterangan, sertifikasi dan perijinan.', 781],
            ['400.7.22.1', 'Surat keterangan', 'Surat keterangan yang diterbitkan pemerintah daerah.', 782],
            ['500.5.7.15', 'Rekomendasi', 'Klasifikasi untuk dokumen rekomendasi.', 1380],
            ['800.1.11.1', 'Surat Perintah Dinas/Surat Tugas', 'Surat perintah dinas dan surat tugas.', 2294],
        ];

        foreach ($classifications as [$code, $name, $description, $sourceOrder]) {
            LetterClassification::query()->updateOrCreate(
                ['source_order' => $sourceOrder],
                [
                    'code' => $code,
                    'name' => $name,
                    'description' => $description,
                    'source' => $source,
                    'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}/{{month_roman}}/{{year}}',
                    'number_padding' => 3,
                    'sort_order' => $sourceOrder,
                    'is_active' => true,
                ],
            );
        }
    }
}
