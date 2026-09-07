<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LetterClassification;
use App\Models\LetterType;
use App\Enums\LetterTypeStatus;
use Illuminate\Database\Seeder;

class LetterTypeRecommendationSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['SKD', 'Surat Keterangan Domisili', 'Surat keterangan domisili atau keterangan tempat tinggal warga.', '400.7.22.1'],
            ['SKU', 'Surat Keterangan Usaha', 'Surat keterangan usaha, kegiatan usaha, UMKM, dan pekerjaan mandiri warga.', '400.7.22.1'],
            ['SKTM', 'Surat Keterangan Tidak Mampu', 'Surat keterangan tidak mampu, kondisi ekonomi, atau kebutuhan layanan sosial warga.', '400.7.22.1'],
            ['SKK', 'Surat Keterangan Kelahiran', 'Surat keterangan kelahiran dan kebutuhan administrasi kependudukan terkait kelahiran.', '400.12.4.4'],
            ['SKM', 'Surat Keterangan Kematian', 'Surat keterangan kematian dan kebutuhan administrasi kependudukan terkait kematian.', '400.12.4.4'],
            ['SPT', 'Surat Perintah Dinas / Surat Tugas', 'Surat tugas, penugasan, perintah dinas, dan penugasan pegawai.', '800.1.11.1'],
            ['UND', 'Surat Undangan Rapat', 'Surat undangan rapat, pertemuan, koordinasi, dan agenda kedinasan.', '000.1.5'],
            ['SR', 'Surat Rekomendasi', 'Surat rekomendasi, saran, pertimbangan, atau persetujuan administratif.', '500.5.7.15'],
            ['SPM', 'Surat Permohonan', 'Surat permohonan, permintaan layanan, permintaan data, atau pengajuan administratif.', '400.7.22.1'],
            ['SPP', 'Surat Pernyataan', 'Surat pernyataan atau pernyataan tertulis untuk kebutuhan administratif.', '400.7.22.1'],
        ];

        foreach ($types as [$code, $name, $description, $classificationCode]) {
            $classification = LetterClassification::query()
                ->where('code', $classificationCode)
                ->where('is_active', true)
                ->first();

            if (! $classification) {
                continue;
            }

            LetterType::query()->updateOrCreate(
                ['tenant_id' => null, 'code' => $code],
                [
                    'letter_classification_id' => $classification->id,
                    'name' => $name,
                    'description' => $description,
                    'status' => LetterTypeStatus::ACTIVE,
                    'variables' => [],
                    'has_expiry' => false,
                    'validity_days' => null,
                    'validity_period' => 'none',
                ],
            );
        }
    }
}
