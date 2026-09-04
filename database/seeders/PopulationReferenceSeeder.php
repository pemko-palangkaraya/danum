<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PopulationReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->upsert('gender', [
            ['code' => 'male', 'label' => 'Laki-laki', 'sort_order' => 1],
            ['code' => 'female', 'label' => 'Perempuan', 'sort_order' => 2],
        ]);

        $this->upsert('blood_type', [
            ['code' => 'A', 'label' => 'A', 'sort_order' => 1],
            ['code' => 'B', 'label' => 'B', 'sort_order' => 2],
            ['code' => 'AB', 'label' => 'AB', 'sort_order' => 3],
            ['code' => 'O', 'label' => 'O', 'sort_order' => 4],
            ['code' => 'unknown', 'label' => 'Tidak diketahui', 'sort_order' => 5],
        ]);

        $this->upsert('marital_status', [
            ['code' => 'single', 'label' => 'Belum Kawin', 'sort_order' => 1],
            ['code' => 'married', 'label' => 'Kawin', 'sort_order' => 2],
            ['code' => 'divorced', 'label' => 'Cerai Hidup', 'sort_order' => 3],
            ['code' => 'widowed', 'label' => 'Cerai Mati', 'sort_order' => 4],
        ]);

        $this->upsert('religion', [
            ['code' => 'islam', 'label' => 'Islam', 'sort_order' => 1],
            ['code' => 'christian', 'label' => 'Kristen', 'sort_order' => 2],
            ['code' => 'catholic', 'label' => 'Katolik', 'sort_order' => 3],
            ['code' => 'hindu', 'label' => 'Hindu', 'sort_order' => 4],
            ['code' => 'buddhist', 'label' => 'Buddha', 'sort_order' => 5],
            ['code' => 'confucian', 'label' => 'Konghucu', 'sort_order' => 6],
            ['code' => 'other', 'label' => 'Kepercayaan/Lainnya', 'sort_order' => 7],
        ]);

        $this->upsert('family_relationship', [
            ['code' => 'head', 'label' => 'Kepala Keluarga', 'sort_order' => 1],
            ['code' => 'spouse', 'label' => 'Istri/Suami', 'sort_order' => 2],
            ['code' => 'child', 'label' => 'Anak', 'sort_order' => 3],
            ['code' => 'son_in_law', 'label' => 'Menantu', 'sort_order' => 4],
            ['code' => 'grandchild', 'label' => 'Cucu', 'sort_order' => 5],
            ['code' => 'parent', 'label' => 'Orang Tua', 'sort_order' => 6],
            ['code' => 'parent_in_law', 'label' => 'Mertua', 'sort_order' => 7],
            ['code' => 'other_family', 'label' => 'Famili Lain', 'sort_order' => 8],
            ['code' => 'domestic_worker', 'label' => 'Pembantu', 'sort_order' => 9],
            ['code' => 'other', 'label' => 'Lainnya', 'sort_order' => 10],
        ]);

        $this->upsert('citizenship', [
            ['code' => 'WNI', 'label' => 'WNI', 'sort_order' => 1],
            ['code' => 'WNA', 'label' => 'WNA', 'sort_order' => 2],
        ]);
    }

    private function upsert(string $group, array $rows): void
    {
        foreach ($rows as $row) {
            $key = [
                'group' => $group,
                'code' => $row['code'],
            ];

            $values = [
                'label' => $row['label'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
                'updated_at' => now(),
            ];

            if (DB::table('population_reference_data')->where($key)->exists()) {
                DB::table('population_reference_data')->where($key)->update($values);
                continue;
            }

            DB::table('population_reference_data')->insert($key + $values + [
                'created_at' => now(),
            ]);
        }
    }
}
