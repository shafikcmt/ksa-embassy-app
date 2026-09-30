<?php

namespace Tests\Unit;

use App\Support\PrintDataMapper;
use PHPUnit\Framework\TestCase;

/** Printed visa/Hijri dates always use English digits; the value itself is unchanged. */
class PrintDataMapperDigitsTest extends TestCase
{
    public function test_arabic_and_persian_digits_become_english(): void
    {
        $this->assertSame('1447/03/15', PrintDataMapper::latinDigits('١٤٤٧/٠٣/١٥'));
        $this->assertSame('1447/03/15', PrintDataMapper::latinDigits('۱۴۴۷/۰۳/۱۵'));
    }

    public function test_english_digits_and_other_text_are_unchanged(): void
    {
        $this->assertSame('1447/03/15', PrintDataMapper::latinDigits('1447/03/15'));
        $this->assertSame('15-01-2024 هـ', PrintDataMapper::latinDigits('١٥-٠١-٢٠٢٤ هـ'));
        $this->assertSame('', PrintDataMapper::latinDigits(null));
    }
}
