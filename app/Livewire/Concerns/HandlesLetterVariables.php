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

    private const DEATH_VARIABLES = [
        'tanggal_meninggal',
        'waktu_meninggal',
        'tempat_meninggal',
        'sebab_meninggal',
    ];

    private const DEATH_AUTOFILLED_VARIABLES = [
        'recipient_name',
        'recipient_nik',
        'recipient_gender',
        'recipient_birth_place',
        'recipient_birth_date',
        'recipient_age',
        'recipient_religion',
        'recipient_occupation',
        'recipient_address',
        'nama_pasangan',
    ];

    public ?string $citizen_id = null;

    public function addRepeaterRow(string $key): void
    {
        $definition = collect(LetterVariableSchema::repeaters($this->variables))->firstWhere('key', $key);
        if (! $definition) {
            return;
        }

        $row = [];
        foreach ($definition['fields'] as $field) {
            $row[$field['key']] = '';
        }

        $this->variableValues[$key] ??= [];
        $this->variableValues[$key][] = $row;
    }

    public function removeRepeaterRow(string $key, int $index): void
    {
        if (! isset($this->variableValues[$key][$index])) {
            return;
        }

        unset($this->variableValues[$key][$index]);
        $this->variableValues[$key] = array_values($this->variableValues[$key]);

        if ($this->variableValues[$key] === []) {
            $this->addRepeaterRow($key);
        }
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

        if (! is_string($citizenId) || ! is_string($letterTypeCode)) {
            return;
        }

        $tenantId = auth()->user()?->tenant_id;
        if (! $tenantId || $letterTypeCode !== CitizenDeathService::LETTER_TYPE_CODE) {
            return;
        }

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
            if ($this->isSystemVariable($variable) || $this->isDeathAutofilledVariable($variable)) {
                continue;
            }

            if ($definition = LetterVariableSchema::parseRepeater($variable)) {
                $rows = $this->variableValues[$definition['key']] ?? [];
                if (! is_array($rows) || $rows === []) {
                    $this->addError('variableValues.' . $definition['key'], 'Tambahkan minimal satu data.');
                    continue;
                }

                foreach ($rows as $rowIndex => $row) {
                    foreach ($definition['fields'] as $field) {
                        if (blank($row[$field['key']] ?? null)) {
                            $this->addError(
                                'variableValues.' . $definition['key'] . '.' . $rowIndex . '.' . $field['key'],
                                'Field ini wajib diisi.'
                            );
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
            if (! $this->isDateVariable($variable)) {
                continue;
            }

            $value = $this->variableValues[$variable] ?? null;
            if (blank($value)) {
                $this->addError('variableValues.' . $variable, 'Tanggal wajib diisi.');
                continue;
            }

            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                $this->addError('variableValues.' . $variable, 'Format tanggal tidak valid.');
                continue;
            }

            if ($value > now()->toDateString()) {
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
        foreach (['number', 'recipient_name', 'recipient_address', 'subject'] as $key) {
            $data[$key] = (string) ($data[$key] ?? '');
        }

        if ($this->citizen_id) {
            $data['_citizen_id'] = $this->citizen_id;
        }

        return $data;
    }

    private function applySystemValues(?PositionHolder $holder = null): void
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant) {
            return;
        }

        $signerName = (string) ($holder?->user?->name ?? $tenant->head_name ?? '');
        $signerTitle = (string) ($holder?->position?->name ?? $tenant->head_title ?? '');
        $values = [
            'tenant_name' => $tenant->name,
            'tenant_city' => $tenant->city,
            'tenant_district' => $tenant->district,
            'tenant_village' => $tenant->village,
            'tenant_province' => $tenant->province,
            'tenant_address' => $tenant->address,
            'tenant_phone' => $tenant->phone,
            'tenant_email' => $tenant->email,
            'tenant_head_name' => $signerName,
            'tenant_head_title' => $signerTitle,
            'nama_ttd' => $signerName,
            'jabatan_ttd' => $signerTitle,
        ];

        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if (! LetterVariableSchema::isRepeater($variable) && $this->isSystemVariable($variable)) {
                $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
            }
        }
    }

    private function applyCitizenValues(Citizen $citizen): void
    {
        $date = static fn ($value): string => $value instanceof Carbon ? $value->format('Y-m-d') : (string) ($value ?? '');
        $values = [
            'citizen_nik' => $citizen->nik,
            'citizen_nama_lengkap' => $citizen->nama_lengkap,
            'citizen_tempat_lahir' => $citizen->tempat_lahir,
            'citizen_tanggal_lahir' => $date($citizen->tanggal_lahir),
            'citizen_jenis_kelamin' => $citizen->jenis_kelamin,
            'citizen_golongan_darah' => $citizen->golongan_darah,
            'citizen_agama' => $citizen->agama,
            'citizen_status_perkawinan' => $citizen->status_perkawinan,
            'citizen_pendidikan' => $citizen->pendidikan,
            'citizen_pekerjaan' => $citizen->pekerjaan,
            'citizen_kewarganegaraan' => $citizen->kewarganegaraan,
            'citizen_no_passport' => $citizen->no_passport,
            'citizen_no_kitap' => $citizen->no_kitap,
            'citizen_nama_ayah' => $citizen->nama_ayah,
            'citizen_nik_ayah' => $citizen->nik_ayah,
            'citizen_nama_ibu' => $citizen->nama_ibu,
            'citizen_nik_ibu' => $citizen->nik_ibu,
            'citizen_status_kependudukan' => $citizen->status_kependudukan,
            'recipient_name' => $citizen->nama_lengkap,
            'recipient_nik' => $citizen->nik,
            'recipient_gender' => $citizen->jenis_kelamin,
            'recipient_birth_place' => $citizen->tempat_lahir,
            'recipient_birth_date' => $date($citizen->tanggal_lahir),
            'recipient_religion' => $citizen->agama,
            'recipient_occupation' => $citizen->pekerjaan,
        ];

        $deathDate = $this->variableValues['tanggal_meninggal'] ?? null;
        if (filled($deathDate) && $citizen->tanggal_lahir) {
            try {
                $values['recipient_age'] = (string) Carbon::parse($citizen->tanggal_lahir)->diffInYears(Carbon::parse($deathDate), false);
            } catch (\Throwable) {
                $values['recipient_age'] = '';
            }
        }

        $family = $this->familyForCitizen($citizen);
        if ($family) {
            $values['recipient_address'] = $this->formatFamilyAddress($family);

            $members = $family->activeMembers
                ->filter(fn ($member) => (string) $member->citizen_id !== (string) $citizen->id)
                ->values();

            $spouse = $members->first(function ($member): bool {
                $relationship = mb_strtolower(trim((string) $member->hubungan_dalam_keluarga));
                return in_array($relationship, ['suami', 'istri', 'pasangan'], true);
            });

            $values['nama_pasangan'] = (string) ($spouse?->citizen?->nama_lengkap ?? '');

            foreach ($this->variables as $variable) {
                $definition = is_string($variable) ? LetterVariableSchema::parseRepeater($variable) : null;
                if (! $definition || $definition['key'] !== 'anak_ditinggalkan') {
                    continue;
                }

                $children = $members
                    ->filter(function ($member): bool {
                        return mb_strtolower(trim((string) $member->hubungan_dalam_keluarga)) === 'anak';
                    })
                    ->values();

                $this->variableValues[$definition['key']] = $children->map(function ($member, int $index): array {
                    return [
                        'nomor' => (string) ($index + 1),
                        'nama' => (string) ($member->citizen?->nama_lengkap ?? ''),
                    ];
                })->all();
                break;
            }
        }

        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if (array_key_exists($variable, $values)) {
                $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
            }
        }
    }

    private function familyForCitizen(Citizen $citizen): ?Family
    {
        $membership = $citizen->activeFamilyMembership()->with('family.activeMembers.citizen')->first();
        if ($membership?->family) {
            return $membership->family;
        }

        return $citizen->headedFamilies()
            ->where('status', 'active')
            ->with('activeMembers.citizen')
            ->first();
    }

    private function formatFamilyAddress(Family $family): string
    {
        return collect([
            $family->alamat,
            filled($family->rt) ? 'RT '.$family->rt : null,
            filled($family->rw) ? 'RW '.$family->rw : null,
            $family->kelurahan,
            $family->kecamatan,
            $family->kabupaten_kota,
            $family->provinsi,
            $family->kode_pos,
        ])->filter(fn ($value) => filled($value))->implode(', ');
    }

    public function isReadOnlyVariable(string $variable): bool
    {
        return $this->isSystemVariable($variable) || $this->isDeathAutofilledVariable($variable);
    }

    private function isSystemVariable(string $variable): bool
    {
        return in_array($variable, self::SYSTEM_VARIABLES, true);
    }

    private function isDeathAutofilledVariable(string $variable): bool
    {
        return $this->citizen_id !== null && in_array($variable, self::DEATH_AUTOFILLED_VARIABLES, true);
    }

    private function isDateVariable(string $variable): bool
    {
        return $variable === 'tanggal_meninggal' || (bool) preg_match('/(^|_)date$/i', $variable);
    }

    private function isBirthDateVariable(string $variable): bool
    {
        return $variable === 'recipient_birth_date' || (bool) preg_match('/(^|_)birth_date$/i', $variable);
    }
}
