<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LetterVariableDefinition;
use Illuminate\Database\Seeder;

class LetterVariableDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['recipient_name', 'Nama', 'text', 'citizen', true, true],
            ['recipient_nik', 'NIK', 'text', 'citizen', true, true],
            ['recipient_gender', 'Jenis Kelamin', 'text', 'citizen', true, true],
            ['recipient_birth_place', 'Tempat Lahir', 'text', 'citizen', true, true],
            ['recipient_birth_date', 'Tanggal Lahir', 'date', 'citizen', true, true],
            ['recipient_age', 'Umur', 'number', 'calculated', true, true],
            ['recipient_religion', 'Agama', 'text', 'citizen', false, true],
            ['recipient_occupation', 'Pekerjaan', 'text', 'citizen', false, true],
            ['recipient_address', 'Alamat', 'textarea', 'family', false, true],
            ['tanggal_meninggal', 'Tanggal Meninggal', 'date', 'manual', true, false],
            ['waktu_meninggal', 'Waktu Meninggal', 'time', 'manual', true, false],
            ['tempat_meninggal', 'Tempat Meninggal', 'text', 'manual', true, false],
            ['sebab_meninggal', 'Sebab Meninggal', 'textarea', 'manual', true, false],
            ['nama_pasangan', 'Nama Pasangan', 'text', 'family', false, true],
        ];

        foreach ($definitions as $sort => [$key, $label, $type, $source, $required, $readonly]) {
            LetterVariableDefinition::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'type' => $type,
                    'source' => $source,
                    'required' => $required,
                    'readonly' => $readonly,
                    'sort_order' => $sort + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
