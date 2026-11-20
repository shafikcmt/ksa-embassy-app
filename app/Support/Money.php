<?php

namespace App\Support;

use App\Models\Plan;
use App\Models\Setting;

/**
 * Multi-currency display for platform billing (plans, subscriptions, license renewal).
 * Presentation only — no conversion is done; every amount is shown in its own currency.
 */
class Money
{
    /** Currency code => label shown in dropdowns. Symbols live in Plan::CURRENCY_SYMBOLS. */
    public static function options(): array
    {
        $out = [];
        foreach (Plan::CURRENCY_SYMBOLS as $code => $symbol) {
            $out[$code] = $code.' ('.trim($symbol).')';
        }

        return $out;
    }

    public static function codes(): array
    {
        return array_keys(Plan::CURRENCY_SYMBOLS);
    }

    /** Super Admin "Default currency" setting (falls back to BDT). */
    public static function defaultCode(): string
    {
        try {
            $code = (string) Setting::get('default_currency', null, 'BDT');
        } catch (\Throwable $e) {
            $code = 'BDT';
        }

        return isset(Plan::CURRENCY_SYMBOLS[$code]) ? $code : 'BDT';
    }

    public static function symbol(?string $code): string
    {
        $code = $code ?: self::defaultCode();

        return Plan::CURRENCY_SYMBOLS[$code] ?? ($code.' ');
    }

    /** "৳ 1,25,000" for BDT (lakh grouping), "$ 1,250.00"-style for the rest. */
    public static function format(float|int|string|null $amount, ?string $code = null, ?int $decimals = null): string
    {
        $code = $code ?: self::defaultCode();
        $symbol = self::symbol($code);
        $symbol = str_ends_with($symbol, ' ') ? $symbol : $symbol.' ';

        if ($code === 'BDT') {
            return $symbol.Bd::number($amount, $decimals ?? 0);
        }

        return $symbol.number_format((float) $amount, $decimals ?? 2);
    }
}
