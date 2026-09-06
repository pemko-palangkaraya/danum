<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterVariableDefinition;

final class LetterVariableDefinitionService
{
    private const SYSTEM_VARIABLES = [
        'letterhead', 'tenant_name', 'tenant_city', 'tenant_district', 'tenant_village',
        'tenant_province', 'tenant_address', 'tenant_phone', 'tenant_email',
        'tenant_head_name', 'tenant_head_title', 'jabatan_ttd', 'nama_ttd', 'tte',
        'citizen_nik', 'citizen_nama_lengkap', 'citizen_tempat_lahir', 'citizen_tanggal_lahir',
        'citizen_jenis_kelamin', 'citizen_golongan_darah', 'citizen_agama', 'citizen_status_perkawinan',
        'citizen_pendidikan', 'citizen_pekerjaan', 'citizen_kewarganegaraan', 'citizen_no_passport',
        'citizen_no_kitap', 'citizen_nama_ayah', 'citizen_nik_ayah', 'citizen_nama_ibu', 'citizen_nik_ibu',
        'citizen_status_kependudukan',
    ];

    /** @var array<string, LetterVariableDefinition>|null */
    private static ?array $cache = null;

    /** @return array<string, LetterVariableDefinition> */
    public function active(): array
    {
        return self::$cache ??= LetterVariableDefinition::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('key')
            ->get()
            ->keyBy('key')
            ->all();
    }

    public function forKey(string $key): ?LetterVariableDefinition
    {
        return $this->active()[$key] ?? null;
    }

    public function isSystem(string $key): bool
    {
        return in_array($key, self::SYSTEM_VARIABLES, true);
    }

    public function isReadonly(string $key, bool $hasCitizen = false): bool
    {
        $definition = $this->forKey($key);
        if ($definition) {
            return (bool) $definition->readonly;
        }

        return $this->isSystem($key) || ($hasCitizen && in_array($key, [
            'recipient_name', 'recipient_nik', 'recipient_gender', 'recipient_birth_place',
            'recipient_birth_date', 'recipient_age', 'recipient_religion', 'recipient_occupation',
            'recipient_address', 'nama_pasangan',
        ], true));
    }
}
