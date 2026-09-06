<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LetterClassification;
use Illuminate\Database\Seeder;

class LetterClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $classifications = [
            ['000', 'Umum', 'Klasifikasi umum dan administrasi umum.', 1],
            ['100', 'Pemerintahan', 'Klasifikasi urusan pemerintahan.', 2],
            ['200', 'Politik', 'Klasifikasi urusan politik dan hubungan kelembagaan.', 3],
            ['300', 'Keamanan dan Ketertiban', 'Klasifikasi urusan keamanan dan ketertiban.', 4],
            ['400', 'Kesejahteraan Rakyat', 'Klasifikasi urusan kesejahteraan rakyat.', 5],
            ['500', 'Perekonomian', 'Klasifikasi urusan perekonomian.', 6],
            ['600', 'Pekerjaan Umum', 'Klasifikasi urusan pekerjaan umum dan pembangunan.', 7],
            ['700', 'Pengawasan', 'Klasifikasi urusan pengawasan.', 8],
            ['800', 'Kepegawaian', 'Klasifikasi urusan kepegawaian.', 9],
            ['900', 'Keuangan', 'Klasifikasi urusan keuangan.', 10],
        ];

        foreach ($classifications as [$code, $name, $description, $sortOrder]) {
            LetterClassification::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'number_format' => '{{number}}/{{classification_code}}/{{tenant_code}}/{{year}}',
                    'number_padding' => 3,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ],
            );
        }
    }
}
