<?php

namespace App\Support;

/**
 * Self-contained number-to-words for Bangladeshi Taka (South-Asian numbering:
 * Crore / Lakh / Thousand / Hundred). No external dependency.
 *
 * Example: 11500     -> "Taka Eleven Thousand Five Hundred Only"
 *          1250000   -> "Taka Twelve Lakh Fifty Thousand Only"
 *          11500.50  -> "Taka Eleven Thousand Five Hundred and Paisa Fifty Only"
 *          0         -> "Taka Zero Only"
 */
class NumberToWords
{
    private const ONES = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
    ];

    private const TENS = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
    ];

    /** Full "Taka {words} Only" string (with paisa when non-zero). */
    public static function taka(float $amount): string
    {
        $amount = round($amount, 2);
        $taka   = (int) floor($amount);
        $paisa  = (int) round(($amount - $taka) * 100);

        $words = 'Taka ' . self::words($taka);

        if ($paisa > 0) {
            $words .= ' and Paisa ' . self::words($paisa);
        }

        return $words . ' Only';
    }

    /** Convert a non-negative integer to South-Asian words (no "Taka"/"Only"). */
    public static function words(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }
        if ($number < 0) {
            return 'Minus ' . self::words(-$number);
        }

        $parts = [];

        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        if ($crore > 0) {
            $parts[] = self::words($crore) . ' Crore';
        }

        $lakh = intdiv($number, 100000);
        $number %= 100000;
        if ($lakh > 0) {
            $parts[] = self::twoDigits($lakh) . ' Lakh';
        }

        $thousand = intdiv($number, 1000);
        $number %= 1000;
        if ($thousand > 0) {
            $parts[] = self::twoDigits($thousand) . ' Thousand';
        }

        $hundred = intdiv($number, 100);
        $number %= 100;
        if ($hundred > 0) {
            $parts[] = self::ones($hundred) . ' Hundred';
        }

        if ($number > 0) {
            $parts[] = self::twoDigits($number);
        }

        return implode(' ', $parts);
    }

    /** 1-99 -> words. */
    private static function twoDigits(int $n): string
    {
        if ($n < 20) {
            return self::ones($n);
        }

        $tens = self::TENS[intdiv($n, 10)];
        $rem  = $n % 10;

        return $rem > 0 ? $tens . ' ' . self::ones($rem) : $tens;
    }

    /** 0-19 single lookup. */
    private static function ones(int $n): string
    {
        return self::ONES[$n] ?? '';
    }
}
