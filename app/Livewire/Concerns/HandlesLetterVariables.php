<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Citizen;
use App\Models\OutgoingLetter;
use App\Services\CitizenDeathService;
use App\Services\LetterVariableDateService;
use App\Services\LetterVariableDefinitionService;
use App\Services\LetterVariableSourceResolver;
use App\Services\OutgoingLetterWorkflowService;
use App\Support\LetterVariableSchema;

trait HandlesLetterVariables
{
    public ?string $citizen_id = null;
    public string $deathTime = '';
    public string $deathTimeZone = 'WIB';

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

    public function repeaterDefinitions(): array { return LetterVariableSchema::repeaters($this->variables); }

    public function mountHandlesLetterVariables(): void
    {
        $citizenId = request()->query('citizen_id');
        $letterTypeCode = request()->query('letter_type_code');
        $tenantId = auth()->user()?->tenant_id;
        if (! is_string($citizenId) || ! is_string($letterTypeCode) || ! $tenantId || $letterTypeCode !== CitizenDeathService::LETTER_TYPE_CODE) return;
        $citizen = Citizen::query()->where('tenant_id', $tenantId)->whereKey($citizenId)->where('status_kependudukan', '!=', 'meninggal')->first();
        if (! $citizen) { $this->dispatch('toast', type: 'error', message: 'Data warga tidak ditemukan, sudah berstatus meninggal, atau bukan milik OPD Anda.'); return; }
        $letterType = app(\App\Services\LetterTypeService::class)->getAvailableForTenant($tenantId)->firstWhere('code', $letterTypeCode);
        if (! $letterType) { $this->dispatch('toast', type: 'error', message: 'Jenis Surat Keterangan Kematian belum tersedia untuk OPD Anda.'); return; }
        $this->citizen_id = $citizen->id;
        $this->deathTime = '';
        $this->deathTimeZone = 'WIB';
        $this->letter_type_id = $letterType->id;
        $this->showForm = true;
        $this->updatedLetterTypeId();
        $this->applyCitizenValues($citizen);
    }

    public function updatedVariableValues($value, string $key): void
    {
        if ($key !== 'tanggal_meninggal' || ! $this->citizen_id) return;
        $citizen = Citizen::query()->where('tenant_id', auth()->user()?->tenant_id)->whereKey($this->citizen_id)->first();
        $this->variableValues['recipient_age'] = $citizen ? app(LetterVariableSourceResolver::class)->age($citizen, $value) : '';
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
        $definitions = app(LetterVariableDefinitionService::class);
        $date = app(LetterVariableDateService::class);
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
                if (! is_array($rows) || $rows === []) { $this->addError('variableValues.'.$repeater['key'], 'Tambahkan minimal satu data.'); continue; }
                foreach ($rows as $rowIndex => $row) foreach ($repeater['fields'] as $field) if (blank($row[$field['key']] ?? null)) $this->addError('variableValues.'.$repeater['key'].'.'.$rowIndex.'.'.$field['key'], 'Field ini wajib diisi.');
                continue;
            }
            $definition = $definitions->forKey($variable);
            $required = $definition?->required ?? true;
            if ($required && blank($this->variableValues[$variable] ?? null)) { $this->addError('variableValues.'.$variable, 'Field ini wajib diisi.'); continue; }
            if (blank($this->variableValues[$variable] ?? null) || ! $date->isDate($variable, $definition?->type)) continue;
            $normalized = $date->normalize($this->variableValues[$variable]);
            if ($normalized === null) { $this->addError('variableValues.'.$variable, 'Format tanggal tidak valid. Gunakan dd mmmm yyyy, misalnya 6 September 2026.'); continue; }
            if ($normalized > now()->toDateString()) $this->addError('variableValues.'.$variable, $date->isBirthDate($variable) ? 'Tanggal lahir tidak boleh tanggal di masa depan.' : 'Tanggal tidak boleh melewati hari ini.');
        }
    }

    public function cancelLetter(string $id, OutgoingLetterWorkflowService $workflow): void
    {
        try {
            $letter = OutgoingLetter::query()->where('tenant_id', auth()->user()?->tenant_id)->findOrFail($id);
            $this->authorize('cancel', $letter);
            $workflow->cancel($letter, auth()->id());
            $this->dispatch('toast', type: 'success', message: 'Draft surat berhasil dibatalkan.');
        } catch (\Throwable $exception) {
            $this->dispatch('toast', type: 'error', message: $exception instanceof \DomainException ? $exception->getMessage() : 'Draft surat gagal dibatalkan.');
        }
    }

    private function normalizedVariableValues(): array
    {
        $data = $this->variableValues;
        $date = app(LetterVariableDateService::class);
        $definitions = app(LetterVariableDefinitionService::class);
        foreach ($data as $key => $value) {
            if (! is_string($key) || is_array($value)) continue;
            $definition = $definitions->forKey($key);
            if ($date->isDate($key, $definition?->type) && filled($value)) $data[$key] = $date->normalize($value) ?? $value;
        }
        if (filled($this->deathTime)) $data['waktu_meninggal'] = trim($this->deathTime).' '.strtoupper($this->deathTimeZone);
        foreach (['number', 'recipient_name', 'recipient_address', 'subject'] as $key) $data[$key] = (string) ($data[$key] ?? '');
        if ($this->citizen_id) $data['_citizen_id'] = $this->citizen_id;
        return $data;
    }

    private function applySystemValues(?\App\Models\PositionHolder $holder = null): void
    {
        $values = app(LetterVariableSourceResolver::class)->system($holder);
        $definitions = app(LetterVariableDefinitionService::class);
        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if ($definitions->isSystem($variable) && array_key_exists($variable, $values)) $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
        }
    }

    private function applyCitizenValues(Citizen $citizen): void
    {
        $definitions = app(LetterVariableDefinitionService::class);
        $resolver = app(LetterVariableSourceResolver::class);
        $values = $resolver->citizen($citizen);
        $family = $resolver->family($citizen);
        $values += $family['values'];
        $deathDate = $this->variableValues['tanggal_meninggal'] ?? null;
        $values['recipient_age'] = $resolver->age($citizen, $deathDate);
        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            $definition = $definitions->forKey($variable);
            if (in_array($definition?->source, ['citizen', 'family', 'calculated'], true) && array_key_exists($variable, $values)) $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
        }
        foreach ($this->repeaterDefinitions() as $repeater) if ($repeater['key'] === 'anak_ditinggalkan') $this->variableValues[$repeater['key']] = $family['children'];
    }

    public function isReadOnlyVariable(string $variable): bool { return app(LetterVariableDefinitionService::class)->isReadonly($variable, $this->citizen_id !== null); }
    public function formatIndonesianDate(mixed $value): string { return app(LetterVariableDateService::class)->format($value); }
    private function isSystemVariable(string $variable): bool { return app(LetterVariableDefinitionService::class)->isSystem($variable); }
    private function isDeathAutofilledVariable(string $variable): bool { return $this->citizen_id !== null && in_array($variable, ['recipient_name', 'recipient_nik', 'recipient_gender', 'recipient_birth_place', 'recipient_birth_date', 'recipient_age', 'recipient_religion', 'recipient_occupation', 'recipient_address', 'nama_pasangan'], true); }
}
