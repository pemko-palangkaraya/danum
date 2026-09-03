<?php

declare(strict_types=1);

namespace App\Support;

final class LetterVariableSchema
{
    /** @return array{key:string,label:string,fields:list<array{key:string,label:string}>} */
    public static function parseRepeater(string $definition): ?array
    {
        if (! preg_match('/^@repeat\s+([A-Za-z_][A-Za-z0-9_]*)\s*\|\s*(.+)$/', trim($definition), $matches)) {
            return null;
        }

        $fields = [];
        foreach (preg_split('/\s*,\s*/', trim($matches[2])) ?: [] as $field) {
            $parts = array_map('trim', explode(':', $field, 2));
            $key = $parts[1] ?? $parts[0];
            $label = $parts[0];
            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) continue;
            $fields[] = ['key' => $key, 'label' => $label ?: ucwords(str_replace('_', ' ', $key))];
        }

        return $fields === [] ? null : ['key' => $matches[1], 'label' => ucwords(str_replace('_', ' ', $matches[1])), 'fields' => $fields];
    }

    public static function isRepeater(string $definition): bool
    {
        return self::parseRepeater($definition) !== null;
    }

    /** @param list<string> $variables @return list<array{key:string,label:string,fields:list<array{key:string,label:string}>}> */
    public static function repeaters(array $variables): array
    {
        $result = [];
        foreach ($variables as $variable) {
            if (is_string($variable) && ($parsed = self::parseRepeater($variable))) $result[] = $parsed;
        }
        return $result;
    }
}
