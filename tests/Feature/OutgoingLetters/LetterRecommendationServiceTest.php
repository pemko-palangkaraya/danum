<?php

declare(strict_types=1);

namespace Tests\Feature\OutgoingLetters;

use App\Models\LetterClassification;
use App\Services\LetterRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_matches_a_classification_from_plain_language(): void
    {
        LetterClassification::factory()->create([
            'code' => '400.10',
            'name' => 'Administrasi Kependudukan',
            'description' => 'Data penduduk dan pelayanan kependudukan.',
            'source' => 'Permendagri Nomor 83 Tahun 2022',
            'is_active' => true,
        ]);

        $results = app(LetterRecommendationService::class)
            ->recommend('saya mau minta data penduduk');

        self::assertNotEmpty($results);
        self::assertSame('400.10', $results[0]['classification']->code);
        self::assertContains('penduduk', $results[0]['matched_terms']);
    }

    public function test_it_returns_no_result_for_unrelated_input(): void
    {
        LetterClassification::factory()->create([
            'code' => '400.10',
            'name' => 'Administrasi Kependudukan',
            'description' => 'Data penduduk dan pelayanan kependudukan.',
            'is_active' => true,
        ]);

        $results = app(LetterRecommendationService::class)
            ->recommend('saya mau membahas resep makanan');

        self::assertSame([], $results);
    }
}
