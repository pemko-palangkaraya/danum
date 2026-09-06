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
        $this->assertSame('2026-09-06', $this->dates->normalize('06 September 2026'));
        $this->assertSame('2026-09-06', $this->dates->normalize('06 Sep 2026'));
        $this->assertSame('2026-05-06', $this->dates->normalize('06 Mei 2026'));
    }

    public function test_it_accepts_iso_date_and_datetime(): void
    {
        $this->assertSame('2026-09-06', $this->dates->normalize('2026-09-06'));
        $this->assertSame('1975-11-26', $this->dates->normalize('1975-11-26 17:55:00'));
    }

    public function test_it_rejects_invalid_date(): void
    {
        $this->assertNull($this->dates->normalize('31 Februari 2026'));
        $this->assertNull($this->dates->normalize('tanggal tidak valid'));
    }

    public function test_it_formats_full_indonesian_date_without_leading_zero(): void
    {
        $this->assertSame('6 September 2026', $this->dates->format('2026-09-06'));
        $this->assertSame('6 Mei 2026', $this->dates->format('2026-05-06'));
        $this->assertSame('31 Agustus 2026', $this->dates->format('31 Agu 2026'));
    }

    public function test_it_recognizes_birth_and_letter_date_variables(): void
    {
        $this->assertTrue($this->dates->isBirthDate('recipient_birth_date'));
        $this->assertTrue($this->dates->isBirthDate('citizen_tanggal_lahir'));
        $this->assertTrue($this->dates->isDate('date'));
        $this->assertTrue($this->dates->isDate('tanggal_meninggal'));
        $this->assertTrue($this->dates->isDate('citizen_tanggal_lahir'));
    }
}
