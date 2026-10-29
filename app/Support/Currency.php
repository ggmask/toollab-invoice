<?php

namespace App\Support;

class Currency
{
    /**
     * Format a number in Indian Rupees format (e.g., ₹ 12,34,567.89)
     */
    public static function format(float|int|string $amount, bool $withSymbol = true, string $symbol = '₹'): string
    {
        $amount = (float)$amount;
        $isNegative = $amount < 0;
        $amount = abs($amount);

        $parts = explode('.', number_format($amount, 2, '.', ''));
        $intPart = $parts[0];
        $decPart = $parts[1];

        if (strlen($intPart) > 3) {
            $last3 = substr($intPart, -3);
            $rest = substr($intPart, 0, -3);
            // Group remainder by 2s from right
            $restFormatted = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $formattedInt = $restFormatted . ',' . $last3;
        } else {
            $formattedInt = $intPart;
        }

        $formatted = $formattedInt . '.' . $decPart;
        if ($isNegative) {
            $formatted = '-' . $formatted;
        }

        return $withSymbol ? $symbol . ' ' . $formatted : $formatted;
    }
}
