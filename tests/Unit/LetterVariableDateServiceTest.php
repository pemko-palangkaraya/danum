<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LetterVariableDateService;
use PHPUnit\Framework\TestCase;

class LetterVariableDateServiceTest extends TestCase
{
    private LetterVariableDateService $dates;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dates = new LetterVariableDateService();
    }

    public function test_it_normalizes_indonesian_date(): void
    {
        $this->assertSame('2026-09-06', $this->dates->normalize('06 Sep 2026'));
        $this->assertSame('2026-05-06', $this->dates->normalize('06 Mei 2026'));
    }

    public function test_it_accepts_iso_date(): void
    {
        $this->assertSame('2026-09-06', $this->dates->normalize('2026-09-06'));
    }

    public function test_it_rejects_invalid_date(): void
    {
        $this->assertNull($this->dates->normalize('31 Feb 2026'));
        $this->assertNull($this->dates->normalize('tanggal tidak valid'));
    }

    public function test_it_formats_indonesian_date(): void
    {
        $this->assertSame('06 Sep 2026', $this->dates->format('2026-09-06'));
        $this->assertSame('06 Mei 2026', $this->dates->format('2026-05-06'));
    }
}
