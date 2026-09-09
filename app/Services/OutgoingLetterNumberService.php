<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterClassification;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OutgoingLetterNumberService
{
    public function generate(
        Tenant $tenant,
        LetterClassification $classification,
        ?Carbon $date = null,
        ?int $manualNumber = null,
    ): string {
        if (! $classification->is_active) {
            throw new \DomainException('Klasifikasi surat tidak aktif.');
        }

        $date ??= now();
        $year = (int) $date->year;
        $number = $manualNumber === null
            ? $this->reserveNext($tenant, $year)
            : $this->reserveManual($tenant, $year, $manualNumber);

        return $this->format($classification, $tenant, $number, $date);
    }

    public function nextNumber(Tenant $tenant, ?Carbon $date = null): int
    {
        $date ??= now();
        $year = (int) $date->year;
        $lastNumber = DB::table('letter_number_sequences')
            ->where('tenant_id', $tenant->id)
            ->where('year', $year)
            ->value('last_number');

        return ((int) $lastNumber) + 1;
    }

    private function reserveNext(Tenant $tenant, int $year): int
    {
        $row = DB::selectOne(
            <<<'SQL'
            INSERT INTO letter_number_sequences
                (id, tenant_id, year, last_number, created_at, updated_at)
            VALUES
                (?, ?, ?, 1, NOW(), NOW())
            ON CONFLICT (tenant_id, year)
            DO UPDATE SET
                last_number = letter_number_sequences.last_number + 1,
                updated_at = NOW()
            RETURNING last_number
            SQL,
            [(string) Str::uuid(), $tenant->id, $year],
        );

        $number = (int) ($row->last_number ?? 0);
        if ($number < 1) {
            throw new \RuntimeException('Nomor surat gagal dibuat.');
        }

        return $number;
    }

    private function reserveManual(Tenant $tenant, int $year, int $manualNumber): int
    {
        if ($manualNumber < 1) {
            throw new \DomainException('Nomor urut surat harus lebih besar dari 0.');
        }

        $row = DB::selectOne(
            <<<'SQL'
            INSERT INTO letter_number_sequences
                (id, tenant_id, year, last_number, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, NOW(), NOW())
            ON CONFLICT (tenant_id, year)
            DO UPDATE SET
                last_number = GREATEST(letter_number_sequences.last_number, EXCLUDED.last_number),
                updated_at = NOW()
            RETURNING last_number
            SQL,
            [(string) Str::uuid(), $tenant->id, $year, $manualNumber],
        );

        $current = (int) ($row->last_number ?? 0);
        if ($current !== $manualNumber) {
            throw new \DomainException(
                "Nomor urut {$manualNumber} sudah terlewati. Nomor terakhir yang tercatat untuk {$year} adalah {$current}.",
            );
        }

        return $manualNumber;
    }

    private function format(LetterClassification $classification, Tenant $tenant, int $number, Carbon $date): string
    {
        $padding = max(1, min((int) $classification->number_padding, 12));
        $values = [
            'number' => str_pad((string) $number, $padding, '0', STR_PAD_LEFT),
            'classification_code' => (string) $classification->code,
            'tenant_code' => (string) $tenant->code,
            'year' => (string) $date->year,
            'month' => $date->format('m'),
            'month_roman' => $this->romanMonth((int) $date->month),
        ];

        $format = trim((string) $classification->number_format);
        if ($format === '') {
            throw new \DomainException('Format penomoran klasifikasi surat belum diatur.');
        }

        foreach (['number', 'classification_code', 'tenant_code'] as $required) {
            if (! preg_match('/\{\{\s*'.preg_quote($required, '/').'\s*\}\}/i', $format)) {
                throw new \DomainException("Format penomoran wajib memiliki variabel {{$required}}.");
            }
        }

        return trim(preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/i',
            fn (array $matches): string => $values[strtolower($matches[1])] ?? $matches[0],
            $format,
        ) ?? $format);
    }

    private function romanMonth(int $month): string
    {
        return match ($month) {
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
            default => '',
        };
    }
}
