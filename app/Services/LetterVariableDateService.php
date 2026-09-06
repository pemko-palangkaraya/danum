<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;

final class LetterVariableDateService
{
    private const MONTHS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    public function format(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            $date = Carbon::parse((string) $value);
            return $date->format('d').' '.self::MONTHS[(int) $date->format('n')].' '.$date->format('Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    public function normalize(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $this->validDate($value);
        }

        if (! preg_match('/^(\d{1,2})\s+([A-Za-z]{3,4})\s+(\d{4})$/u', $value, $matches)) {
            return null;
        }

        $month = array_search(mb_strtolower($matches[2]), array_map('mb_strtolower', self::MONTHS), true);
        $month ??= $this->englishMonthNumber($matches[2]);

        if (! $month) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!j-n-Y', $matches[1].'-'.$month.'-'.$matches[3])->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function isBirthDate(string $key): bool
    {
        return $key === 'recipient_birth_date' || (bool) preg_match('/(^|_)birth_date$/i', $key);
    }

    public function isDate(string $key, ?string $type = null): bool
    {
        return $type === 'date' || $key === 'tanggal_meninggal' || (bool) preg_match('/(^|_)date$/i', $key);
    }

    private function validDate(string $value): ?string
    {
        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->format('Y-m-d') === $value ? $value : null;
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
}
