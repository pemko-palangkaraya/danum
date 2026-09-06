<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PopulationReferenceService
{
    /** @var array<string, array<string, string>> */
    private array $labelCache = [];

    /** @var array<string, array<string, string>> */
    private array $codeCache = [];

    public function all(): array
    {
        return [
            'gender' => $this->group('gender'),
            'blood_type' => $this->group('blood_type'),
            'marital_status' => $this->group('marital_status'),
            'religion' => $this->group('religion'),
            'citizenship' => $this->group('citizenship'),
            'family_relationship' => $this->group('family_relationship'),
            'population_status' => $this->group('population_status'),
        ];
    }

    public function group(string $group): Collection
    {
        return DB::table('population_reference_data')
            ->where('group', $group)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['code', 'label']);
    }

    /**
     * Return a code-to-label map for a reference group.
     *
     * The result is cached for the lifetime of this service instance so
     * multiple presentation contexts do not repeat the same database query.
     */
    public function labels(string $group): array
    {
        return $this->labelCache[$group] ??= $this->group($group)
            ->pluck('label', 'code')
            ->all();
    }

    public function label(string $group, ?string $code, string $fallback = '-'): string
    {
        if ($code === null || $code === '') {
            return $fallback;
        }

        return $this->labels($group)[$code] ?? $fallback;
    }

    /**
     * Resolve either a stored code or its human-readable label to the
     * canonical active code. Matching is case-insensitive and whitespace-safe.
     */
    public function codeForValue(string $group, ?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($value));

        if (! isset($this->codeCache[$group])) {
            $this->codeCache[$group] = [];

            foreach ($this->group($group) as $reference) {
                $this->codeCache[$group][mb_strtolower((string) $reference->code)] = (string) $reference->code;
                $this->codeCache[$group][mb_strtolower((string) $reference->label)] = (string) $reference->code;
            }
        }

        return $this->codeCache[$group][$normalized] ?? null;
    }
}
