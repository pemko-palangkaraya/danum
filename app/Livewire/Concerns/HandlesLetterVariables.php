<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Citizen;
use App\Models\OutgoingLetter;
use App\Services\CitizenDeathService;
use App\Services\CitizenService;
use App\Services\LetterVariableDateService;
use App\Services\LetterVariableDefinitionService;
use App\Services\LetterVariableSourceResolver;
use App\Services\OutgoingLetterService;
use App\Services\OutgoingLetterWorkflowService;
use App\Support\LetterVariableSchema;

trait HandlesLetterVariables
{
    public ?string $citizen_id = null;
    public string $deathTime = '';
    public string $deathTimeZone = 'WIB';
    public bool $showCancelConfirm = false;
    public string $cancelId = '';

    protected CitizenService $citizenService;
    protected LetterVariableDateService $letterVariableDateService;
    protected LetterVariableDefinitionService $letterVariableDefinitionService;
    protected LetterVariableSourceResolver $letterVariableSourceResolver;

    public function bootHandlesLetterVariables(
        CitizenService $citizenService,
        LetterVariableDateService $letterVariableDateService,
        LetterVariableDefinitionService $letterVariableDefinitionService,
        LetterVariableSourceResolver $letterVariableSourceResolver,
    ): void {
        $this->citizenService = $citizenService;
        $this->letterVariableDateService = $letterVariableDateService;
        $this->letterVariableDefinitionService = $letterVariableDefinitionService;
        $this->letterVariableSourceResolver = $letterVariableSourceResolver;
    }

    public function addRepeaterRow(string $key): void
    {
        $definition = collect($this->repeaterDefinitions())->firstWhere('key', $key);
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

    public function repeaterDefinitions(): array
    {
        return LetterVariableSchema::repeaters($this->variables);
    }

    public function mountHandlesLetterVariables(): void
    {
        $citizenId = request()->query('citizen_id');
        $letterTypeCode = request()->query('letter_type_code');
        $tenantId = auth()->user()?->tenant_id;

        if (! is_string($citizenId) || ! is_string($letterTypeCode) || ! $tenantId || $letterTypeCode !== CitizenDeathService::LETTER_TYPE_CODE) return;

        $citizen = $this->citizenService->findAliveForTenant($tenantId, $citizenId);
        if (! $citizen) {
            $this->dispatch('toast', type: 'error', message: 'Data warga tidak ditemukan, sudah berstatus meninggal, atau bukan milik OPD Anda.');
            return;
        }

        $letterType = $this->letterTypeService->getAvailableForTenant($tenantId)->firstWhere('code', $letterTypeCode);
        if (! $letterType) {
            $this->dispatch('toast', type: 'error', message: 'Jenis Surat Keterangan Kematian belum tersedia untuk OPD Anda.');
            return;
        }

        $this->citizen_id = $citizen->id;
        $this->deathTime = '';
        $this->deathTimeZone = 'WIB';
        $this->letter_type_id = $letterType->id;
        $this->showForm = true;
        $this->updatedLetterTypeId();
        $this->applyCitizenValues($citizen);
    }

    public function createFresh(): void
    {
        $this->create();
        if (! $this->showForm) return;

        $this->reset(['citizen_id', 'deathTime', 'deathTimeZone']);
        $this->resetValidation();
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->reset([
            'editingId',
            'letter_type_id',
            'signer_position_id',
            'validator_position_id',
            'variables',
            'variableValues',
            'citizen_id',
            'deathTime',
            'deathTimeZone',
        ]);
        $this->resetValidation();
    }

    public function openCancel(string $id): void
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            if (! $tenantId) throw new \DomainException('Akun platform tidak memiliki konteks tenant.');

            $letter = app(OutgoingLetterService::class)->find($id, $tenantId);
            if (! $letter) throw new \DomainException('Surat tidak ditemukan dalam konteks tenant Anda.');

            $this->authorize('cancel', $letter);
            $this->cancelId = $id;
            $this->showCancelConfirm = true;
        } catch (\Throwable $exception) {
            $this->dispatch('toast', type: 'error', message: $exception instanceof \DomainException ? $exception->getMessage() : 'Draft surat tidak dapat dibatalkan.');
        }
    }

    public function closeCancel(): void
    {
        $this->showCancelConfirm = false;
        $this->cancelId = '';
    }

    public function updatedVariableValues($value, string $key): void
    {
        if ($key === 'recipient_nik') {
            $this->lookupCitizenByNik((string) $value);
            return;
        }

        if ($key !== 'tanggal_meninggal' || ! $this->citizen_id) return;

        $tenantId = auth()->user()?->tenant_id;
        if (! $tenantId) return;

        $citizen = $this->citizenService->findAliveForTenant($tenantId, $this->citizen_id);
        $this->variableValues['recipient_age'] = $citizen
            ? $this->letterVariableSourceResolver->age($citizen, $value)
            : '';
    }

    private function lookupCitizenByNik(string $nik): void
    {
        $nik = preg_replace('/\D+/', '', $nik) ?? '';
        $this->variableValues['recipient_nik'] = $nik;

        if (strlen($nik) !== 16) return;

        $tenantId = auth()->user()?->tenant_id;
        if (! $tenantId) return;

        $citizen = $this->citizenService->query($tenantId, '', null)
            ->where('nik', $nik)
            ->first();

        if (! $citizen) {
            $this->citizen_id = null;
            $this->dispatch('toast', type: 'error', message: 'NIK tidak ditemukan pada data warga OPD Anda.');
            return;
        }

        $this->citizen_id = $citizen->id;
        $this->applyCitizenValues($citizen);
        $this->dispatch('toast', type: 'success', message: 'Data warga ditemukan dan diisikan otomatis.');
    }

    private function initializeVariableValues(bool $newRows = false): void
    {
        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if ($definition = LetterVariableSchema::parseRepeater($variable)) {
                $this->variableValues[$definition['key']] ??= $newRows ? [[]] : [];
                continue;
            }
            $this->variableValues[$variable] ??= '';
        }
        $this->hydrateDeathTimeInput();
    }

    private function hydrateDeathTimeInput(): void
    {
        $value = trim((string) ($this->variableValues['waktu_meninggal'] ?? ''));
        if ($value === '') return;
        if (preg_match('/^(\d{1,2}:\d{2})(?::\d{2})?\s*(WIB|WITA|WIT)?$/i', $value, $matches)) {
            $this->deathTime = $matches[1];
            if (! empty($matches[2])) $this->deathTimeZone = strtoupper($matches[2]);
        }
    }

    private function validateVariableValues(): void
    {
        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if ($this->isSystemVariable($variable) || $this->isDeathAutofilledVariable($variable)) continue;
            if ($variable === 'waktu_meninggal') {
                if (blank($this->deathTime)) $this->addError('variableValues.waktu_meninggal', 'Waktu meninggal wajib diisi.');
                elseif (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $this->deathTime)) $this->addError('variableValues.waktu_meninggal', 'Format waktu meninggal tidak valid.');
                if (! in_array(strtoupper($this->deathTimeZone), ['WIB', 'WITA', 'WIT'], true)) $this->addError('variableValues.waktu_meninggal', 'Zona waktu tidak valid.');
                continue;
            }
            if ($repeater = LetterVariableSchema::parseRepeater($variable)) {
                $rows = $this->variableValues[$repeater['key']] ?? [];
                if (! is_array($rows) || $rows === []) {
                    $this->addError('variableValues.'.$repeater['key'], 'Tambahkan minimal satu data.');
                    continue;
                }
                foreach ($rows as $rowIndex => $row) {
                    foreach ($repeater['fields'] as $field) {
                        if (blank($row[$field['key']] ?? null)) $this->addError('variableValues.'.$repeater['key'].'.'.$rowIndex.'.'.$field['key'], 'Field ini wajib diisi.');
                    }
                }
                continue;
            }
            $definition = $this->letterVariableDefinitionService->forKey($variable);
            $required = $definition?->required ?? true;
            if ($required && blank($this->variableValues[$variable] ?? null)) {
                $this->addError('variableValues.'.$variable, 'Field ini wajib diisi.');
                continue;
            }
            if (blank($this->variableValues[$variable] ?? null) || ! $this->letterVariableDateService->isDate($variable, $definition?->type)) continue;
            $normalized = $this->letterVariableDateService->normalize($this->variableValues[$variable]);
            if ($normalized === null) {
                $this->addError('variableValues.'.$variable, 'Format tanggal tidak valid. Gunakan dd mmmm yyyy, misalnya 6 September 2026.');
                continue;
            }
            if ($normalized > now()->toDateString()) $this->addError('variableValues.'.$variable, $this->letterVariableDateService->isBirthDate($variable) ? 'Tanggal lahir tidak boleh tanggal di masa depan.' : 'Tanggal tidak boleh melewati hari ini.');
        }
    }

    public function cancelLetter(string $id, OutgoingLetterWorkflowService $workflow, OutgoingLetterService $letters): void
    {
        try {
            $tenantId = auth()->user()?->tenant_id;
            if (! $tenantId) throw new \DomainException('Akun platform tidak memiliki konteks tenant.');

            $letter = $letters->find($id, $tenantId);
            if (! $letter) throw new \DomainException('Surat tidak ditemukan dalam konteks tenant Anda.');

            $this->authorize('cancel', $letter);
            $workflow->cancel($letter, auth()->id());
            $this->closeCancel();
            $this->dispatch('toast', type: 'success', message: 'Draft surat berhasil dibatalkan.');
        } catch (\Throwable $exception) {
            $this->closeCancel();
            $this->dispatch('toast', type: 'error', message: $exception instanceof \DomainException ? $exception->getMessage() : 'Draft surat gagal dibatalkan.');
        }
    }

    private function normalizedVariableValues(): array
    {
        $data = $this->variableValues;
        foreach ($data as $key => $value) {
            if (! is_string($key) || is_array($value)) continue;
            $definition = $this->letterVariableDefinitionService->forKey($key);
            if ($this->letterVariableDateService->isDate($key, $definition?->type) && filled($value)) $data[$key] = $this->letterVariableDateService->normalize($value) ?? $value;
        }
        if (filled($this->deathTime)) $data['waktu_meninggal'] = trim($this->deathTime).' '.strtoupper($this->deathTimeZone);
        foreach (['number', 'recipient_name', 'recipient_address', 'subject'] as $key) $data[$key] = (string) ($data[$key] ?? '');
        if ($this->citizen_id) $data['_citizen_id'] = $this->citizen_id;
        return $data;
    }

    private function applySystemValues(?\App\Models\PositionHolder $holder = null): void
    {
        $values = $this->letterVariableSourceResolver->system($holder);
        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if ($this->letterVariableDefinitionService->isSystem($variable) && array_key_exists($variable, $values)) $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
        }
    }

    private function applyCitizenValues(Citizen $citizen): void
    {
        $values = $this->letterVariableSourceResolver->citizen($citizen);
        $family = $this->letterVariableSourceResolver->family($citizen);
        $values += $family['values'];
        $deathDate = $this->variableValues['tanggal_meninggal'] ?? null;
        $values['recipient_age'] = $this->letterVariableSourceResolver->age($citizen, $deathDate);
        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            $definition = $this->letterVariableDefinitionService->forKey($variable);
            if (in_array($definition?->source, ['citizen', 'family', 'calculated'], true) && array_key_exists($variable, $values)) $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
        }
        $this->variableValues['ak'] = $family['members'];
        foreach ($this->repeaterDefinitions() as $repeater) if ($repeater['key'] === 'anak_ditinggalkan') $this->variableValues[$repeater['key']] = $family['children'];
    }

    public function isReadOnlyVariable(string $variable): bool
    {
        return $this->letterVariableDefinitionService->isReadonly($variable, $this->citizen_id !== null);
    }

    public function formatIndonesianDate(mixed $value): string
    {
        return $this->letterVariableDateService->format($value);
    }

    private function isSystemVariable(string $variable): bool
    {
        return $this->letterVariableDefinitionService->isSystem($variable);
    }

    private function isDeathAutofilledVariable(string $variable): bool
    {
        return $this->citizen_id !== null && in_array($variable, ['recipient_name', 'recipient_nik', 'recipient_gender', 'recipient_birth_place', 'recipient_birth_date', 'recipient_age', 'recipient_religion', 'recipient_occupation', 'recipient_address', 'nama_pasangan'], true);
    }
}
