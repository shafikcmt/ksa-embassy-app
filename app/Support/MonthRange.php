<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** "Month" list filter: a Y-m value becomes that calendar month's first/last day. */
class MonthRange
{
    /** @return array{0:string,1:string}|null  [from, to] as Y-m-d, or null for a missing/invalid month */
    public static function bounds(?string $month): ?array
    {
        if (! is_string($month) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return null;
        }
        $start = CarbonImmutable::createFromFormat('!Y-m', $month);

        return [$start->startOfMonth()->toDateString(), $start->endOfMonth()->toDateString()];
    }
}
