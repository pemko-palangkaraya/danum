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

        $classifications = [
            ['000', 'Umum', 'Klasifikasi umum dan administrasi umum.', 1],
            ['100', 'Pemerintahan', 'Klasifikasi urusan pemerintahan.', 2],
            ['200', 'Politik', 'Klasifikasi urusan politik dan hubungan kelembagaan.', 3],
            ['300', 'Keamanan dan Ketertiban', 'Klasifikasi urusan keamanan dan ketertiban.', 4],
            ['400', 'Kesejahteraan Rakyat', 'Klasifikasi urusan kesejahteraan rakyat.', 5],
            ['500', 'Perekonomian', 'Klasifikasi urusan perekonomian.', 6],
            ['600', 'Pekerjaan Umum dan Ketenagaan', 'Klasifikasi urusan pekerjaan umum dan ketenagaan.', 7],
            ['700', 'Pengawasan', 'Klasifikasi urusan pengawasan.', 8],
            ['800', 'Kepegawaian', 'Klasifikasi urusan kepegawaian.', 9],
            ['900', 'Keuangan', 'Klasifikasi urusan keuangan.', 10],

            // Klasifikasi yang paling sering menjadi tujuan surat layanan masyarakat.
            ['400.7.22', 'Surat Keterangan, Sertifikasi dan Perijinan', 'Surat keterangan, sertifikasi dan perijinan.', 400722],
            ['400.7.22.1', 'Surat keterangan', 'Surat keterangan yang diterbitkan pemerintah daerah.', 4007221],
            ['400.12', 'Kependudukan dan Catatan Sipil', 'Urusan kependudukan dan catatan sipil.', 4001200],
            ['400.12.4', 'Pengelolaan Informasi Administrasi Kependudukan', 'Pengelolaan informasi administrasi kependudukan.', 4001240],
            ['400.12.4.4', 'Penyajian dan Layanan Informasi Administrasi Kependudukan', 'Penyajian dan layanan informasi administrasi kependudukan.', 4001244],
            ['500.5.7.15', 'Rekomendasi', 'Klasifikasi untuk dokumen rekomendasi.', 5005715],
            ['800.1.11.1', 'Surat Perintah Dinas/Surat Tugas', 'Surat perintah dinas dan surat tugas.', 8001111],
            ['000.1.5', 'Rapat pimpinan antara lain: Notula/Risalah Rapat', 'Administrasi rapat pimpinan dan notula/risalah rapat.', 105],
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
