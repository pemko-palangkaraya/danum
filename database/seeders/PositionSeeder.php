<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\TenantCategory;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Seed common Indonesian government/public-service positions.
     *
     * Position data is grouped by tenant category so it can be reused by
     * tenants representing different types of government institutions.
     */
    public function run(): void
    {
        $positions = [
            'sekretariat-daerah' => [
                ['SEKDA', 'Sekretaris Daerah', true, true],
                ['ASDA-1', 'Asisten Pemerintahan dan Kesejahteraan Rakyat', true, true],
                ['ASDA-2', 'Asisten Perekonomian dan Pembangunan', true, true],
                ['ASDA-3', 'Asisten Administrasi Umum', true, true],
                ['KABAG', 'Kepala Bagian', false, true],
                ['KASUBAG', 'Kepala Subbagian', false, true],
            ],
            'sekretariat-dprd' => [
                ['SEKWAN', 'Sekretaris DPRD', true, true],
                ['KABAG-UMUM', 'Kepala Bagian Umum', false, true],
                ['KABAG-PERSIDANGAN', 'Kepala Bagian Persidangan', false, true],
                ['KABAG-PERUNDANGAN', 'Kepala Bagian Perundang-undangan', false, true],
                ['KASUBAG', 'Kepala Subbagian', false, true],
            ],
            'inspektorat' => [
                ['IRDA', 'Inspektur Daerah', true, true],
                ['SEKRETARIS', 'Sekretaris Inspektorat', true, true],
                ['IRBAN-1', 'Inspektur Pembantu Wilayah I', false, true],
                ['IRBAN-2', 'Inspektur Pembantu Wilayah II', false, true],
                ['IRBAN-3', 'Inspektur Pembantu Wilayah III', false, true],
                ['IRBAN-4', 'Inspektur Pembantu Wilayah IV', false, true],
            ],
            'dinas' => [
                ['KEPALA-DINAS', 'Kepala Dinas', true, true],
                ['SEKRETARIS-DINAS', 'Sekretaris Dinas', true, true],
                ['KABID', 'Kepala Bidang', false, true],
                ['KASUBAG-UMPEG', 'Kepala Subbagian Umum dan Kepegawaian', false, true],
                ['KASUBAG-PERENCANAAN', 'Kepala Subbagian Perencanaan dan Keuangan', false, true],
                ['KASI', 'Kepala Seksi', false, true],
            ],
            'badan' => [
                ['KEPALA-BADAN', 'Kepala Badan', true, true],
                ['SEKRETARIS-BADAN', 'Sekretaris Badan', true, true],
                ['KABID', 'Kepala Bidang', false, true],
                ['KASUBAG-UMUM', 'Kepala Subbagian Umum dan Kepegawaian', false, true],
                ['KASUBAG-PERENCANAAN', 'Kepala Subbagian Perencanaan dan Keuangan', false, true],
            ],
            'satuan-polisi-pamong-praja' => [
                ['KASAT', 'Kepala Satuan Polisi Pamong Praja', true, true],
                ['SEKRETARIS', 'Sekretaris Satuan', true, true],
                ['KABID', 'Kepala Bidang', false, true],
                ['KASI', 'Kepala Seksi', false, true],
            ],
            'kecamatan' => [
                ['CAMAT', 'Camat', true, true],
                ['SEKCAM', 'Sekretaris Kecamatan', true, true],
                ['KASI-PEMERINTAHAN', 'Kepala Seksi Pemerintahan', false, true],
                ['KASI-PEMBANGUNAN', 'Kepala Seksi Pemberdayaan Masyarakat dan Desa', false, true],
                ['KASI-KESRA', 'Kepala Seksi Kesejahteraan Sosial', false, true],
                ['KASUBAG-UMUM', 'Kepala Subbagian Umum dan Kepegawaian', false, true],
            ],
            'kelurahan' => [
                ['LURAH', 'Lurah', true, true],
                ['SEKLUR', 'Sekretaris Kelurahan', true, true],
                ['KASI-PEMERINTAHAN', 'Kepala Seksi Pemerintahan, Ketenteraman dan Ketertiban', false, true],
                ['KASI-EKBANG', 'Kepala Seksi Perekonomian dan Pembangunan', false, true],
                ['KASI-KESRA', 'Kepala Seksi Kesejahteraan Sosial', false, true],
            ],
            'uptd' => [
                ['KEPALA-UPTD', 'Kepala UPTD', true, true],
                ['KASUBAG-TU', 'Kepala Subbagian Tata Usaha', false, true],
                ['KASI', 'Kepala Seksi', false, true],
            ],
            'unit-pelaksana-teknis' => [
                ['KEPALA-UPT', 'Kepala Unit Pelaksana Teknis', true, true],
                ['KASUBAG-TU', 'Kepala Subbagian Tata Usaha', false, true],
                ['KOORDINATOR', 'Koordinator', false, true],
            ],
            'rumah-sakit-daerah' => [
                ['DIREKTUR', 'Direktur Rumah Sakit Daerah', true, true],
                ['WADIR', 'Wakil Direktur', true, true],
                ['KABAG', 'Kepala Bagian', false, true],
                ['KABID', 'Kepala Bidang', false, true],
                ['KASUBAG', 'Kepala Subbagian', false, true],
            ],
            'puskesmas' => [
                ['KEPALA-PUSKESMAS', 'Kepala Puskesmas', true, true],
                ['KASUBAG-TU', 'Kepala Subbagian Tata Usaha', false, true],
                ['PENANGGUNG-JAWAB-UPAYA', 'Penanggung Jawab Upaya Kesehatan', false, true],
                ['PENANGGUNG-JAWAB-MUTU', 'Penanggung Jawab Mutu', false, true],
            ],
            'blud' => [
                ['DIREKTUR', 'Direktur BLUD', true, true],
                ['WADIR', 'Wakil Direktur', true, true],
                ['KEPALA-BAGIAN', 'Kepala Bagian', false, true],
                ['KEPALA-BIDANG', 'Kepala Bidang', false, true],
            ],
            'lainnya' => [
                ['KEPALA-UNIT', 'Kepala Unit', true, true],
                ['SEKRETARIS', 'Sekretaris', true, true],
                ['KOORDINATOR', 'Koordinator', false, true],
                ['KEPALA-TU', 'Kepala Tata Usaha', false, true],
                ['KEPALA-SEKSI', 'Kepala Seksi', false, true],
            ],
        ];

        foreach ($positions as $categoryCode => $items) {
            $category = TenantCategory::query()->where('code', $categoryCode)->first();

            if ($category === null) {
                $this->command?->warn("Kategori tenant '{$categoryCode}' tidak ditemukan, dilewati.");
                continue;
            }

            foreach ($items as [$code, $name, $canSign, $canValidate]) {
                Position::query()->updateOrCreate(
                    [
                        'tenant_category_id' => $category->id,
                        'code' => $code,
                    ],
                    [
                        'name' => $name,
                        'description' => "Jabatan {$name} pada kategori {$category->name}.",
                        'status' => PositionStatus::ACTIVE,
                        'can_sign' => $canSign,
                        'can_validate' => $canValidate,
                    ],
                );
            }

            $this->command?->info("{$category->name}: " . count($items) . ' jabatan siap.');
        }
    }
}
