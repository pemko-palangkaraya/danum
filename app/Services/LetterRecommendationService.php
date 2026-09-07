<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterClassification;
use App\Models\LetterType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final class LetterRecommendationService
{
    public function recommend(string $input, ?string $tenantId = null, int $limit = 5): array
    {
        $query = $this->normalize($input);
        if ($query === '') {
            return [];
        }

        $letterTypes = $this->letterTypes($tenantId);
        $results = [];

        foreach ($letterTypes as $letterType) {
            $classification = $letterType->classification;
            if (! $classification || ! $classification->is_active) {
                continue;
            }

            $candidate = $this->normalize(implode(' ', array_filter([
                $letterType->name,
                $letterType->description,
                $classification->name,
                $classification->description,
                $classification->source,
            ])));

            [$score, $matched] = $this->score($query, $candidate);
            if ($score <= 0) {
                continue;
            }

            $results[] = [
                'letter_type' => $letterType,
                'classification' => $classification,
                'confidence' => round(min(0.99, $score), 2),
                'matched_terms' => $matched,
            ];
        }

        if ($results === []) {
            return $this->classificationFallback($query, $limit);
        }

        usort($results, fn (array $a, array $b): int => $b['confidence'] <=> $a['confidence']);

        return array_slice($results, 0, $limit);
    }

    private function letterTypes(?string $tenantId): Collection
    {
        if ($tenantId === null) {
            return LetterType::query()
                ->where('status', 'active')
                ->with('classification')
                ->get();
        }

        return app(LetterTypeService::class)
            ->getAvailableForTenant($tenantId)
            ->load('classification');
    }

    private function score(string $query, string $candidate): array
    {
        $queryTerms = $this->terms($query);
        $candidateTerms = $this->terms($candidate);
        $candidateText = ' '.implode(' ', $candidateTerms).' ';
        $matched = [];
        $points = 0.0;

        foreach ($queryTerms as $term) {
            if (in_array($term, $candidateTerms, true)) {
                $points += 0.18;
                $matched[] = $term;
                continue;
            }

            foreach ($this->synonyms($term) as $synonym) {
                if (str_contains($candidateText, ' '.$synonym.' ')) {
                    $points += 0.12;
                    $matched[] = $term;
                    break;
                }
            }
        }

        if ($query !== '' && str_contains($candidate, $query)) {
            $points += 0.25;
        }

        return [min(0.99, $points), array_values(array_unique($matched))];
    }

    private function classificationFallback(string $query, int $limit): array
    {
        $results = [];

        foreach (LetterClassification::query()->where('is_active', true)->get() as $classification) {
            $candidate = $this->normalize(implode(' ', array_filter([
                $classification->name,
                $classification->description,
                $classification->source,
            ])));
            [$score, $matched] = $this->score($query, $candidate);

            if ($score > 0) {
                $results[] = [
                    'letter_type' => null,
                    'classification' => $classification,
                    'confidence' => round(min(0.89, $score), 2),
                    'matched_terms' => $matched,
                ];
            }
        }

        usort($results, fn (array $a, array $b): int => $b['confidence'] <=> $a['confidence']);

        return array_slice($results, 0, $limit);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/u', ' ')
            ->squish()
            ->value();
    }

    private function terms(string $value): array
    {
        $stopWords = [
            'saya', 'mau', 'ingin', 'buat', 'bikin', 'membuat', 'surat', 'untuk', 'dengan',
            'yang', 'ke', 'dari', 'dan', 'di', 'agar', 'akan', 'adalah', 'ini', 'itu',
        ];

        return array_values(array_filter(
            explode(' ', $this->normalize($value)),
            fn (string $term): bool => strlen($term) >= 3 && ! in_array($term, $stopWords, true),
        ));
    }

    private function synonyms(string $term): array
    {
        return [
            'minta' => ['permintaan', 'permohonan', 'meminta'],
            'meminta' => ['permintaan', 'permohonan', 'minta'],
            'permohonan' => ['permintaan', 'meminta', 'minta'],
            'undang' => ['undangan', 'mengundang'],
            'mengundang' => ['undangan', 'undang'],
            'rapat' => ['pertemuan', 'koordinasi'],
            'pertemuan' => ['rapat', 'koordinasi'],
            'tugas' => ['penugasan', 'menugaskan'],
            'perjalanan' => ['dinas'],
            'laporan' => ['pelaporan'],
            'data' => ['informasi', 'dokumen'],
            'penduduk' => ['kependudukan', 'warga'],
            'rekomendasi' => ['saran', 'persetujuan'],
        ][$term] ?? [];
    }
}
