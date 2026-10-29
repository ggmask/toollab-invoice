<?php

namespace App\Support;

class NumberToWords
{
    private static array $ones = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
        15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
    ];

    private static array $tens = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
    ];

    public static function toWords(float|int|string $number): string
    {
        return self::convertIndianCurrency($number);
    }

    public static function convertIndianCurrency(float|int|string $number): string
    {
        $number = round((float)$number, 2);
        if ($number <= 0) {
            return 'Zero Rupees Only';
        }

        $parts = explode('.', number_format($number, 2, '.', ''));
        $rupees = (int)$parts[0];
        $paise = (int)$parts[1];

        $words = self::convertIndianWholeNumber($rupees);
        $result = 'INR ' . ($words ?: 'Zero') . ' Rupees';

        if ($paise > 0) {
            $paiseWords = self::convertIndianWholeNumber($paise);
            $result .= ' and ' . $paiseWords . ' Paise';
        }

        return $result . ' Only';
    }

    private static function convertIndianWholeNumber(int $num): string
    {
        if ($num == 0) {
            return '';
        }

        $crores = intdiv($num, 10000000);
        $num %= 10000000;

        $lakhs = intdiv($num, 100000);
        $num %= 100000;

        $thousands = intdiv($num, 1000);
        $num %= 1000;

        $hundreds = intdiv($num, 100);
        $remainder = $num % 100;

        $parts = [];

        if ($crores > 0) {
            $parts[] = self::convertTwoDigits($crores) . ' Crore';
        }
        if ($lakhs > 0) {
            $parts[] = self::convertTwoDigits($lakhs) . ' Lakh';
        }
        if ($thousands > 0) {
            $parts[] = self::convertTwoDigits($thousands) . ' Thousand';
        }
        if ($hundreds > 0) {
            $parts[] = self::$ones[$hundreds] . ' Hundred';
        }
        if ($remainder > 0) {
            $parts[] = self::convertTwoDigits($remainder);
        }

        return implode(' ', $parts);
    }

    private static function convertTwoDigits(int $num): string
    {
        if ($num < 20) {
            return self::$ones[$num];
        }
        $ten = intdiv($num, 10);
        $unit = $num % 10;
        return self::$tens[$ten] . ($unit > 0 ? '-' . self::$ones[$unit] : '');
    }
}
