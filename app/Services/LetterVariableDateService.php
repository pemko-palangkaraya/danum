<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Carbon;

final class LetterVariableDateService
{
    private const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private const MONTH_ALIASES = [
        'jan' => 1, 'januari' => 1,
        'feb' => 2, 'februari' => 2,
        'mar' => 3, 'maret' => 3,
        'apr' => 4, 'april' => 4,
        'mei' => 5, 'may' => 5,
        'jun' => 6, 'juni' => 6,
        'jul' => 7, 'juli' => 7,
        'agu' => 8, 'aug' => 8, 'agustus' => 8,
        'sep' => 9, 'september' => 9,
        'okt' => 10, 'oct' => 10, 'oktober' => 10,
        'nov' => 11, 'november' => 11,
        'des' => 12, 'dec' => 12, 'desember' => 12,
    ];

    public function format(mixed $value): string
    {
        $normalized = $this->normalize($value);
        if ($normalized === null) return blank($value) ? '' : (string) $value;

        $date = Carbon::createFromFormat('!Y-m-d', $normalized);
        return $date->format('j').' '.self::MONTHS[(int) $date->format('n')].' '.$date->format('Y');
    }

    public function normalize(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) return $value->format('Y-m-d');

        $value = trim((string) $value);
        if ($value === '') return null;

        if (preg_match('/^(\d{4}-\d{2}-\d{2})(?:[T\s].*)?$/', $value, $matches)) return $this->validDate($matches[1]);
        if (! preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/u', $value, $matches)) return null;

        $month = self::MONTH_ALIASES[mb_strtolower($matches[2])] ?? null;
        if ($month === null) return null;

        $candidate = sprintf('%04d-%02d-%02d', (int) $matches[3], $month, (int) $matches[1]);
        return $this->validDate($candidate);
    }

    public function isBirthDate(string $key): bool
    {
        return in_array($key, ['recipient_birth_date', 'citizen_tanggal_lahir'], true)
            || (bool) preg_match('/(^|_)birth_date$/i', $key)
            || (bool) preg_match('/(^|_)tanggal_lahir$/i', $key);
    }

    public function isDate(string $key, ?string $type = null): bool
    {
        return $type === 'date'
            || in_array($key, ['date', 'tanggal_meninggal', 'citizen_tanggal_lahir'], true)
            || (bool) preg_match('/(^|_)birth_date$/i', $key)
            || (bool) preg_match('/(^|_)tanggal_(lahir|meninggal)$/i', $key)
            || (bool) preg_match('/(^|_)date$/i', $key);
    }

    private function validDate(string $value): ?string
    {
        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->format('Y-m-d') === $value ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
