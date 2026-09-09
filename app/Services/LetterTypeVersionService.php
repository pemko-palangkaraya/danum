<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Models\LetterTypeVersion;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class LetterTypeVersionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly LetterVariableDefinitionService $variableDefinitions,
    ) {}

    public function current(LetterType $letterType): ?LetterTypeVersion
    {
        return $this->active($letterType);
    }

    public function active(LetterType $letterType, ?Carbon $at = null): ?LetterTypeVersion
    {
        $at ??= now();

        return LetterTypeVersion::query()
            ->where('letter_type_id', $letterType->id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhere('effective_from', '<=', $at))
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhere('effective_until', '>', $at))
            ->orderByDesc('version')
            ->first();
    }

    public function create(LetterType $letterType, array $data, int $createdBy): LetterTypeVersion
    {
        $this->ensureGlobal($letterType);

        return DB::transaction(function () use ($letterType, $data, $createdBy): LetterTypeVersion {
            $effectiveFrom = $this->normalizeDate($data['effective_from'] ?? now());
            $effectiveUntil = ! empty($data['effective_until'])
                ? $this->normalizeDate($data['effective_until'])
                : null;
            $latest = $this->latest($letterType);

            $this->validatePeriod($latest, $effectiveFrom, $effectiveUntil);

            $variables = $this->normalizeVariables($data['variables'] ?? $letterType->variables ?? []);
            $this->validateCatalog($variables);
            $currentVariables = $this->normalizeVariables($letterType->variables ?? []);
            $missing = array_values(array_diff($currentVariables, $variables));

            if ($missing !== []) {
                throw new \DomainException(
                    'Versi baru tidak boleh menghapus variabel yang sudah tersedia pada jenis surat: '.implode(', ', $missing).'.'
                );
            }

            if ($latest !== null && $latest->effective_until === null) {
                $latest->update(['effective_until' => $effectiveFrom]);
            }

            $version = LetterTypeVersion::query()->create([
                'letter_type_id' => $letterType->id,
                'version' => ($latest?->version ?? 0) + 1,
                'body_template' => (string) ($data['body_template'] ?? $letterType->body_template ?? ''),
                'template_path' => $data['template_path'] ?? $letterType->template_path,
                'variables' => $variables,
                'effective_from' => $effectiveFrom,
                'effective_until' => $effectiveUntil,
                'is_active' => true,
                'change_note' => trim((string) ($data['change_note'] ?? '')) ?: null,
                'created_by' => $createdBy,
            ]);

            $this->auditLogService->record(
                'letter_type.version.created',
                User::query()->find($createdBy),
                $version,
                null,
                [
                    'letter_type_id' => $version->letter_type_id,
                    'version' => $version->version,
                    'template_path' => $version->template_path,
                    'variables' => $version->variables,
                    'effective_from' => $version->effective_from?->toIso8601String(),
                    'effective_until' => $version->effective_until?->toIso8601String(),
                    'change_note' => $version->change_note,
                ],
            );

            return $version;
        });
    }

    public function ensureCurrent(LetterType $letterType): ?LetterTypeVersion
    {
        $latest = $this->latest($letterType);
        $bodyTemplate = (string) ($letterType->body_template ?? '');
        $templatePath = $letterType->template_path;
        $variables = $this->normalizeVariables($letterType->variables ?? []);
        $this->validateCatalog($variables);
        $active = $this->active($letterType);

        if ($active !== null && $active->body_template === $bodyTemplate && $active->template_path === $templatePath) {
            return $active;
        }

        if ($latest !== null && $latest->body_template === $bodyTemplate && $latest->template_path === $templatePath) {
            return $active ?? $latest;
        }

        if ($letterType->body_template === null && $letterType->template_path === null) {
            return null;
        }

        $effectiveFrom = $this->normalizeDate(now());

        if ($latest !== null && $latest->effective_until === null && $latest->effective_from !== null && $effectiveFrom->gte($latest->effective_from)) {
            $latest->update(['effective_until' => $effectiveFrom]);
        }

        return LetterTypeVersion::query()->create([
            'letter_type_id' => $letterType->id,
            'version' => ($latest?->version ?? 0) + 1,
            'body_template' => $bodyTemplate,
            'template_path' => $templatePath,
            'variables' => $variables,
            'effective_from' => $effectiveFrom,
            'is_active' => true,
        ]);
    }

    private function latest(LetterType $letterType): ?LetterTypeVersion
    {
        return LetterTypeVersion::query()
            ->where('letter_type_id', $letterType->id)
            ->orderByDesc('version')
            ->first();
    }

    private function validatePeriod(?LetterTypeVersion $latest, Carbon $from, ?Carbon $until): void
    {
        if ($latest?->effective_from && $from->lte($latest->effective_from)) {
            throw new \DomainException('Versi baru harus memiliki periode mulai setelah versi terakhir.');
        }

        if ($latest?->effective_until && $from->lt($latest->effective_until)) {
            throw new \DomainException('Periode versi baru tidak boleh bertumpang tindih dengan versi sebelumnya.');
        }

        if ($until !== null && $until->lte($from)) {
            throw new \DomainException('Tanggal selesai harus lebih besar dari tanggal mulai.');
        }
    }

    private function normalizeDate(mixed $value): Carbon
    {
        $date = $value instanceof CarbonInterface ? $value->copy() : Carbon::parse($value);

        return $date->startOfSecond();
    }

    private function normalizeVariables(array $variables): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            $variables,
        ))));
    }

    /** @param list<string> $variables */
    private function validateCatalog(array $variables): void
    {
        $undefined = $this->variableDefinitions->undefined($variables);
        if ($undefined === []) return;

        throw new \DomainException(
            'Variabel template belum terdaftar di Katalog Variabel: '.implode(', ', $undefined).'.'
        );
    }

    private function ensureGlobal(LetterType $letterType): void
    {
        if (! $letterType->isGlobal()) {
            throw new \InvalidArgumentException('Template version hanya dapat dikelola untuk jenis surat global.');
        }
    }
}
