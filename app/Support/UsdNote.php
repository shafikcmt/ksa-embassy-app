<?php

namespace App\Support;

/**
 * Reads a dollar amount out of an old free-text expense note, e.g.
 *   "Enjaz Dollars Top Up $10.55*1 Card" → 10.55      "Enjaz Dollars Top Up $420" → 420
 *   "Top Up $10.55*3"                    → 31.65      (an "*N" / "x N" suffix is a quantity)
 * Returns a "1234.56" string, or null when the note has no $ amount.
 */
class UsdNote
{
    public static function parse(?string $note): ?string
    {
        if ($note === null || ! preg_match('/\$\s*([0-9][0-9,]*(?:\.[0-9]+)?)(?:\s*[*xX×]\s*([0-9]+))?/u', $note, $m)) {
            return null;
        }
        $amount = (float) str_replace(',', '', $m[1]) * (isset($m[2]) && (int) $m[2] > 0 ? (int) $m[2] : 1);

        return $amount > 0 && $amount < 1e10 ? number_format($amount, 2, '.', '') : null;
    }
}
