<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Support\NumberToWords;

final class NumberToWordsTest extends TestCase
{
    public function testStandardAmountConversion(): void
    {
        $words = NumberToWords::convertIndianCurrency(12345.50);
        $this->assertStringContainsString('Twelve Thousand', $words);
        $this->assertStringContainsString('Three Hundred', $words);
        $this->assertStringContainsString('Forty-Five', $words);
        $this->assertStringContainsString('Fifty Paise', $words);
    }

    public function testLakhsAndCroresConversion(): void
    {
        $words = NumberToWords::convertIndianCurrency(1500000.00); // 15 Lakhs
        $this->assertStringContainsString('Fifteen Lakh', $words);

        $wordsCrore = NumberToWords::convertIndianCurrency(25000000.00); // 2.5 Crores
        $this->assertStringContainsString('Two Crore', $wordsCrore);
        $this->assertStringContainsString('Fifty Lakh', $wordsCrore);
    }

    public function testOverHundredCroresRecursion(): void
    {
        // 1,500 Crores (15,00,00,00,000)
        $words = NumberToWords::convertIndianCurrency(15000000000.00);
        $this->assertStringContainsString('One Thousand Five Hundred Crore', $words);
    }
}
