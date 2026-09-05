<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\PositionHolder;
use App\Services\CitizenDeathService;
use App\Support\LetterVariableSchema;
use Illuminate\Support\Carbon;

trait HandlesLetterVariables
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

    private const DEATH_AUTOFILLED_VARIABLES = [
        'recipient_name', 'recipient_nik', 'recipient_gender', 'recipient_birth_place',
        'recipient_birth_date', 'recipient_age', 'recipient_religion', 'recipient_occupation',
        'recipient_address', 'nama_pasangan',
    ];

    private const INDONESIAN_MONTHS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    public ?string $citizen_id = null;

    public function addRepeaterRow(string $key): void
    {
        $definition = collect(LetterVariableSchema::repeaters($this->variables))->firstWhere('key', $key);
        if (! $definition) return;
        $row = [];
        foreach ($definition['fields'] as $field) $row[$field['key']] = '';
        $this->variableValues[$key] ??= [];
        $this->variableValues[$key][] = $row;
    }

    public function removeRepeaterRow(string $key, int $index): void
    {
        if (! isset($this->variableValues[$key][$index])) return;
        unset($this->variableValues[$key][$index]);
        $this->variableValues[$key] = array_values($this->variableValues[$key]);
        if ($this->variableValues[$key] === []) $this->addRepeaterRow($key);
    }

    /** @return list<array{key:string,label:string,fields:list<array{key:string,label:string}>}> */
    public function repeaterDefinitions(): array
    {
        return LetterVariableSchema::repeaters($this->variables);
    }

    public function mountHandlesLetterVariables(): void
    {
        $citizenId = request()->query('citizen_id');
        $letterTypeCode = request()->query('letter_type_code');
        if (! is_string($citizenId) || ! is_string($letterTypeCode)) return;

        $tenantId = auth()->user()?->tenant_id;
        if (! $tenantId || $letterTypeCode !== CitizenDeathService::LETTER_TYPE_CODE) return;

        $citizen = Citizen::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($citizenId)
            ->where('status_kependudukan', '!=', 'meninggal')
            ->first();

        if (! $citizen) {
            $this->dispatch('toast', type: 'error', message: 'Data warga tidak ditemukan, sudah berstatus meninggal, atau bukan milik OPD Anda.');
            return;
        }

        $letterType = app(\App\Services\LetterTypeService::class)
            ->getAvailableForTenant($tenantId)
            ->firstWhere('code', $letterTypeCode);
        if (! $letterType) {
            $this->dispatch('toast', type: 'error', message: 'Jenis Surat Keterangan Kematian belum tersedia untuk OPD Anda.');
            return;
        }

        $this->citizen_id = $citizen->id;
        $this->letter_type_id = $letterType->id;
        $this->showForm = true;
        $this->updatedLetterTypeId();
        $this->applyCitizenValues($citizen);
    }

    public function updatedVariableValues($value, string $key): void
    {
        if ($key !== 'tanggal_meninggal' || ! $this->citizen_id) return;

        $normalized = $this->normalizeIndonesianDate($value);
        $citizen = Citizen::query()
            ->where('tenant_id', auth()->user()?->tenant_id)
            ->whereKey($this->citizen_id)
            ->first();

        if (! $citizen || ! $citizen->tanggal_lahir || $normalized === null) {
            $this->variableValues['recipient_age'] = '';
            return;
        }

        try {
            $age = Carbon::parse($citizen->tanggal_lahir)->diffInYears(Carbon::parse($normalized), false);
            $this->variableValues['recipient_age'] = $age >= 0 ? (string) $age : '';
        } catch (\Throwable) {
            $this->variableValues['recipient_age'] = '';
        }
    }

    private function initializeVariableValues(bool $newRows = false): void
    {
        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if ($definition = LetterVariableSchema::parseRepeater($variable)) {
                $this->variableValues[$definition['key']] ??= $newRows ? [[]] : [];
            } else {
                $this->variableValues[$variable] ??= '';
            }
        }
    }

    private function validateVariableValues(): void
    {
        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if ($this->isSystemVariable($variable) || $this->isDeathAutofilledVariable($variable)) continue;

            if ($definition = LetterVariableSchema::parseRepeater($variable)) {
                $rows = $this->variableValues[$definition['key']] ?? [];
                if (! is_array($rows) || $rows === []) {
                    $this->addError('variableValues.' . $definition['key'], 'Tambahkan minimal satu data.');
                    continue;
                }
                foreach ($rows as $rowIndex => $row) {
                    foreach ($definition['fields'] as $field) {
                        if (blank($row[$field['key']] ?? null)) {
                            $this->addError('variableValues.' . $definition['key'] . '.' . $rowIndex . '.' . $field['key'], 'Field ini wajib diisi.');
                        }
                    }
                }
                continue;
            }

            if (blank($this->variableValues[$variable] ?? null)) {
                $this->addError('variableValues.' . $variable, 'Field ini wajib diisi.');
            }
        }

        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if (! $this->isDateVariable($variable)) continue;
            $value = $this->variableValues[$variable] ?? null;
            if (blank($value)) {
                $this->addError('variableValues.' . $variable, 'Tanggal wajib diisi.');
                continue;
            }

            $normalized = $this->normalizeIndonesianDate($value);
            if ($normalized === null) {
                $this->addError('variableValues.' . $variable, 'Format tanggal tidak valid. Gunakan dd mmm yyyy, misalnya 06 Sep 2026.');
                continue;
            }

            if ($normalized > now()->toDateString()) {
                $message = $this->isBirthDateVariable($variable)
                    ? 'Tanggal lahir tidak boleh tanggal di masa depan.'
                    : 'Tanggal tidak boleh melewati hari ini.';
                $this->addError('variableValues.' . $variable, $message);
            }
        }
    }

    /** @return array<string,mixed> */
    private function normalizedVariableValues(): array
    {
        $data = $this->variableValues;
        foreach (['number', 'recipient_name', 'recipient_address', 'subject'] as $key) $data[$key] = (string) ($data[$key] ?? '');
        if ($this->citizen_id) $data['_citizen_id'] = $this->citizen_id;
        return $data;
    }

    private function applySystemValues(?PositionHolder $holder = null): void
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant) return;
        $signerName = (string) ($holder?->user?->name ?? $tenant->head_name ?? '');
        $signerTitle = (string) ($holder?->position?->name ?? $tenant->head_title ?? '');
        $values = [
            'tenant_name' => $tenant->name, 'tenant_city' => $tenant->city, 'tenant_district' => $tenant->district,
            'tenant_village' => $tenant->village, 'tenant_province' => $tenant->province, 'tenant_address' => $tenant->address,
            'tenant_phone' => $tenant->phone, 'tenant_email' => $tenant->email, 'tenant_head_name' => $signerName,
            'tenant_head_title' => $signerTitle, 'nama_ttd' => $signerName, 'jabatan_ttd' => $signerTitle,
        ];
        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if (! LetterVariableSchema::isRepeater($variable) && $this->isSystemVariable($variable)) $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
        }
    }

    private function applyCitizenValues(Citizen $citizen): void
    {
        $date = fn ($value): string => $this->formatIndonesianDate($value);
        $values = [
            'citizen_nik' => $citizen->nik, 'citizen_nama_lengkap' => $citizen->nama_lengkap,
            'citizen_tempat_lahir' => $citizen->tempat_lahir, 'citizen_tanggal_lahir' => $date($citizen->tanggal_lahir),
            'citizen_jenis_kelamin' => $this->formatGender($citizen->jenis_kelamin), 'citizen_golongan_darah' => $citizen->golongan_darah,
            'citizen_agama' => $citizen->agama, 'citizen_status_perkawinan' => $citizen->status_perkawinan,
            'citizen_pendidikan' => $citizen->pendidikan, 'citizen_pekerjaan' => $citizen->pekerjaan,
            'citizen_kewarganegaraan' => $citizen->kewarganegaraan, 'citizen_no_passport' => $citizen->no_passport,
            'citizen_no_kitap' => $citizen->no_kitap, 'citizen_nama_ayah' => $citizen->nama_ayah,
            'citizen_nik_ayah' => $citizen->nik_ayah, 'citizen_nama_ibu' => $citizen->nama_ibu,
            'citizen_nik_ibu' => $citizen->nik_ibu, 'citizen_status_kependudukan' => $citizen->status_kependudukan,
            'recipient_name' => $citizen->nama_lengkap, 'recipient_nik' => $citizen->nik,
            'recipient_gender' => $this->formatGender($citizen->jenis_kelamin), 'recipient_birth_place' => $citizen->tempat_lahir,
            'recipient_birth_date' => $date($citizen->tanggal_lahir), 'recipient_religion' => $citizen->agama,
            'recipient_occupation' => $citizen->pekerjaan,
        ];

        $deathDate = $this->normalizeIndonesianDate($this->variableValues['tanggal_meninggal'] ?? null);
        if ($deathDate && $citizen->tanggal_lahir) {
            try {
                $age = Carbon::parse($citizen->tanggal_lahir)->diffInYears(Carbon::parse($deathDate), false);
                $values['recipient_age'] = $age >= 0 ? (string) $age : '';
            } catch (\Throwable) {
                $values['recipient_age'] = '';
            }
        }

        $family = $this->familyForCitizen($citizen);
        $values['nama_pasangan'] = '-';
        foreach ($this->variables as $variable) {
            $definition = is_string($variable) ? LetterVariableSchema::parseRepeater($variable) : null;
            if ($definition && $definition['key'] === 'anak_ditinggalkan') {
                $this->variableValues[$definition['key']] = [['nomor' => '1', 'nama' => '-']];
                break;
            }
        }

        if ($family) {
            $values['recipient_address'] = $this->formatFamilyAddress($family);
            $members = $family->activeMembers->filter(fn ($member) => (string) $member->citizen_id !== (string) $citizen->id)->values();
            $spouse = $members->first(function ($member): bool {
                return in_array(mb_strtolower(trim((string) $member->hubungan_dalam_keluarga)), ['suami', 'istri', 'pasangan'], true);
            });
            $values['nama_pasangan'] = (string) ($spouse?->citizen?->nama_lengkap ?: '-');

            foreach ($this->variables as $variable) {
                $definition = is_string($variable) ? LetterVariableSchema::parseRepeater($variable) : null;
                if (! $definition || $definition['key'] !== 'anak_ditinggalkan') continue;
                $children = $members->filter(fn ($member): bool => mb_strtolower(trim((string) $member->hubungan_dalam_keluarga)) === 'anak')->values();
                $this->variableValues[$definition['key']] = $children->isEmpty()
                    ? [['nomor' => '1', 'nama' => '-']]
                    : $children->map(fn ($member, int $index): array => ['nomor' => (string) ($index + 1), 'nama' => (string) ($member->citizen?->nama_lengkap ?: '-')])->all();
                break;
            }
        }

        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if (array_key_exists($variable, $values)) $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
        }
    }

    private function familyForCitizen(Citizen $citizen): ?Family
    {
        $membership = $citizen->activeFamilyMembership()->with('family.activeMembers.citizen')->first();
        if ($membership?->family) return $membership->family;
        return $citizen->headedFamilies()->where('status', 'active')->with('activeMembers.citizen')->first();
    }

    private function formatFamilyAddress(Family $family): string
    {
        return collect([$family->alamat, filled($family->rt) ? 'RT '.$family->rt : null, filled($family->rw) ? 'RW '.$family->rw : null, $family->kelurahan, $family->kecamatan, $family->kabupaten_kota, $family->provinsi, $family->kode_pos])->filter(fn ($value) => filled($value))->implode(', ');
    }

    public function isReadOnlyVariable(string $variable): bool
    {
        return $this->isSystemVariable($variable) || $this->isDeathAutofilledVariable($variable);
    }

    public function formatIndonesianDate($value): string
    {
        if (blank($value)) return '';
        try {
            $date = Carbon::parse((string) $value);
            return $date->format('d').' '.self::INDONESIAN_MONTHS[(int) $date->format('n')].' '.$date->format('Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function normalizeIndonesianDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;
        if (! preg_match('/^(\d{1,2})\s+([A-Za-z]{3,4})\s+(\d{4})$/u', $value, $matches)) return null;
        $monthMap = array_change_key_case(array_flip(self::INDONESIAN_MONTHS), CASE_LOWER);
        $month = $monthMap[mb_strtolower($matches[2])] ?? $this->englishMonthNumber($matches[2]);
        if (! $month) return null;
        try {
            return Carbon::createFromFormat('!j-n-Y', $matches[1].'-'.$month.'-'.$matches[3])->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function englishMonthNumber(string $month): ?int
    {
        return [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        ][mb_strtolower(substr(trim($month), 0, 3))] ?? null;
    }

    private function formatGender($value): string
    {
        return match (mb_strtolower(trim((string) $value))) {
            'male', 'laki-laki', 'laki laki', 'l' => 'Laki-laki',
            'female', 'perempuan', 'p' => 'Perempuan',
            default => (string) $value,
        };
    }

    private function isSystemVariable(string $variable): bool { return in_array($variable, self::SYSTEM_VARIABLES, true); }
    private function isDeathAutofilledVariable(string $variable): bool { return $this->citizen_id !== null && in_array($variable, self::DEATH_AUTOFILLED_VARIABLES, true); }
    private function isDateVariable(string $variable): bool { return $variable === 'tanggal_meninggal' || (bool) preg_match('/(^|_)date$/i', $variable); }
    private function isBirthDateVariable(string $variable): bool { return $variable === 'recipient_birth_date' || (bool) preg_match('/(^|_)birth_date$/i', $variable); }
}
