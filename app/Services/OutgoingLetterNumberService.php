<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterClassification;
use App\Models\LetterNumberSequence;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class OutgoingLetterNumberService
{
    public function generate(Tenant $tenant, LetterClassification $classification, ?Carbon $date = null): string
    {
        if (! $classification->is_active) {
            throw new \DomainException('Klasifikasi surat tidak aktif.');
        }

        $date ??= now();
        $year = (int) $date->year;

        return DB::transaction(function () use ($tenant, $classification, $date, $year): string {
            $sequence = LetterNumberSequence::query()
                ->where('tenant_id', $tenant->id)
                ->where('letter_classification_id', $classification->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                try {
                    $sequence = LetterNumberSequence::query()->create([
                        'tenant_id' => $tenant->id,
                        'letter_classification_id' => $classification->id,
                        'year' => $year,
                        'last_number' => 1,
                    ]);
                } catch (QueryException $exception) {
                    $sequence = LetterNumberSequence::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('letter_classification_id', $classification->id)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->first();

                    if ($sequence === null) {
                        throw $exception;
                    }

                    $sequence->increment('last_number');
                    $sequence->refresh();
                }
            } else {
                $sequence->increment('last_number');
                $sequence->refresh();
            }

            return $this->format($classification, $tenant, $sequence->last_number, $date);
        });
    }

    private function format(
        LetterClassification $classification,
        Tenant $tenant,
        int $number,
        Carbon $date,
    ): string {
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
