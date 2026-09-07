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
            ['rt', 'RT', 'text', 'family', false, true],
            ['rw', 'RW', 'text', 'family', false, true],
            ['nama_pasangan', 'Nama Pasangan', 'text', 'family', false, true],
            ['nama', 'Nama', 'text', 'citizen', true, true],
            ['nik', 'NIK', 'text', 'citizen', true, true],
            ['jenis_kelamin', 'Jenis Kelamin', 'text', 'citizen', true, true],
            ['tpt_lahir', 'Tempat Lahir', 'text', 'citizen', true, true],
            ['tanggal_lahir', 'Tanggal Lahir', 'date', 'citizen', true, true],
            ['status_perkawinan', 'Status Perkawinan', 'text', 'citizen', false, true],
            ['agama', 'Agama', 'text', 'citizen', false, true],
            ['pekerjaan', 'Pekerjaan', 'text', 'citizen', false, true],
            ['kewarganegaraan', 'Kewarganegaraan', 'text', 'citizen', false, true],
            ['status_kependudukan', 'Status Kependudukan', 'text', 'citizen', false, true],
            ['alamat', 'Alamat', 'textarea', 'family', false, true],
            ['tanggal_meninggal', 'Tanggal Meninggal', 'date', 'manual', true, false],
            ['waktu_meninggal', 'Waktu Meninggal', 'time', 'manual', true, false],
            ['tempat_meninggal', 'Tempat Meninggal', 'text', 'manual', true, false],
            ['sebab_meninggal', 'Sebab Meninggal', 'textarea', 'manual', true, false],
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
