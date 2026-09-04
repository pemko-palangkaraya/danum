<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\PositionHolder;
use App\Support\LetterVariableSchema;

trait HandlesLetterVariables
{
    private const SYSTEM_VARIABLES = [
        'letterhead', 'tenant_name', 'tenant_city', 'tenant_district', 'tenant_village',
        'tenant_province', 'tenant_address', 'tenant_phone', 'tenant_email',
        'tenant_head_name', 'tenant_head_title', 'tte',
    ];

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
            if ($this->isSystemVariable($variable)) {
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

        return $data;
    }

    private function applySystemValues(?PositionHolder $holder = null): void
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant) {
            return;
        }

        $values = [
            'tenant_name' => $tenant->name,
            'tenant_city' => $tenant->city,
            'tenant_district' => $tenant->district,
            'tenant_village' => $tenant->village,
            'tenant_province' => $tenant->province,
            'tenant_address' => $tenant->address,
            'tenant_phone' => $tenant->phone,
            'tenant_email' => $tenant->email,
            'tenant_head_name' => $holder?->user?->name ?? $tenant->head_name,
            'tenant_head_title' => $holder?->position?->name ?? $tenant->head_title,
        ];

        foreach ($this->variables as $variable) {
            $variable = (string) $variable;
            if (! LetterVariableSchema::isRepeater($variable) && $this->isSystemVariable($variable)) {
                $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
            }
        }
    }

    private function isSystemVariable(string $variable): bool
    {
        return in_array($variable, self::SYSTEM_VARIABLES, true);
    }

    private function isDateVariable(string $variable): bool
    {
        return (bool) preg_match('/(^|_)date$/i', $variable);
    }

    private function isBirthDateVariable(string $variable): bool
    {
        return (bool) preg_match('/(^|_)birth_date$/i', $variable);
    }
}
