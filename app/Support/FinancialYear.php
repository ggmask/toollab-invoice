<?php

namespace App\Support;

class FinancialYear
{
    /**
     * Get Indian Financial Year for a given date string or timestamp (e.g. '2026-10-29' -> '2026-27')
     */
    public static function forDate(?string $date = null): string
    {
        $timestamp = $date ? strtotime($date) : time();
        $year = (int)date('Y', $timestamp);
        $month = (int)date('n', $timestamp);

        if ($month >= 4) {
            $startYear = $year;
            $endYear = substr((string)($year + 1), -2);
        } else {
            $startYear = $year - 1;
            $endYear = substr((string)$year, -2);
        }

        return "{$startYear}-{$endYear}";
    }

    public static function current(): string
    {
        return self::forDate();
    }

    /**
     * Get start and end date of a financial year (e.g. '2026-27' -> ['2026-04-01', '2027-03-31'])
     */
    public static function dateRange(string $fy): array
    {
        $parts = explode('-', $fy);
        $startYear = (int)$parts[0];
        $endYear = $startYear + 1;

        return [
            "{$startYear}-04-01",
            "{$endYear}-03-31",
        ];
    }
}
