<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\DocumentNumberService;
use App\Support\FinancialYear;
use App\Support\Database;

final class DocumentNumberServiceTest extends TestCase
{
    private int $businessId = 1;
    private string $fy;

    protected function setUp(): void
    {
        $this->fy = FinancialYear::current();
    }

    public function testGenerateDraftNumberFormat(): void
    {
        $draftNum1 = DocumentNumberService::generateDraftNumber($this->businessId, 4, $this->fy);
        $draftNum2 = DocumentNumberService::generateDraftNumber($this->businessId, 4, $this->fy);

        $this->assertStringStartsWith('DFT-', $draftNum1);
        $this->assertStringStartsWith('DFT-', $draftNum2);
        $this->assertNotEquals($draftNum1, $draftNum2, 'Consecutive drafts must have distinct numbers');
    }

    public function testRule46LengthCapOnDocumentNumbering(): void
    {
        // 1. Standard Prefix (INV)
        $num1 = DocumentNumberService::formatNumber('INV', $this->fy, 1, 4);
        $this->assertLessThanOrEqual(16, strlen($num1), "INV format should not exceed 16 chars: {$num1}");

        // 2. Custom 5-char Prefix (TOOL1)
        $num2 = DocumentNumberService::formatNumber('TOOL1', $this->fy, 42, 4);
        $this->assertLessThanOrEqual(16, strlen($num2), "TOOL1 format should not exceed 16 chars: {$num2}");

        // 3. Fallback on Long Prefix
        $num3 = DocumentNumberService::formatNumber('TOOLCORP', $this->fy, 1, 4);
        $this->assertLessThanOrEqual(16, strlen($num3), "Long prefix must fall back to stay <= 16 chars: {$num3}");
    }

    public function testFinancialYearCalculation(): void
    {
        $fyMay = FinancialYear::forDate('2026-05-15');
        $this->assertEquals('2026-27', $fyMay);

        $fyFeb = FinancialYear::forDate('2026-02-10');
        $this->assertEquals('2025-26', $fyFeb);
    }
}
