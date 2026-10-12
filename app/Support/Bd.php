<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Bangladesh-friendly display helpers (presentation only — nothing is stored or converted in the DB):
 * Taka with lakh/crore digit grouping, Dhaka local time, Bangla digits / month and weekday names,
 * and a Bangla + English greeting by Dhaka hour.
 */
class Bd
{
    public const TZ = 'Asia/Dhaka';

    private const DIGITS = ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];

    private const MONTHS = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

    private const WEEKDAYS = ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার', 'শনিবার'];

    /** 125000 → "১,২৫,০০০"-style grouping (lakh/crore), kept in Latin digits: "1,25,000". */
    public static function number(float|int|string|null $value, int $decimals = 0): string
    {
        $n = round((float) $value, $decimals);
        $neg = $n < 0;
        [$int, $dec] = array_pad(explode('.', number_format(abs($n), $decimals, '.', '')), 2, '');

        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        if ($rest !== '') {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $int = $rest . ',' . $last3;
        }

        return ($neg ? '-' : '') . $int . ($dec !== '' ? '.' . $dec : '');
    }

    /** "৳ 1,25,000" (or with paisa when $decimals = 2). */
    public static function taka(float|int|string|null $value, int $decimals = 0): string
    {
        return '৳ ' . self::number($value, $decimals);
    }

    /** Latin digits → Bangla digits. */
    public static function digits(string|int|null $text): string
    {
        return strtr((string) $text, self::DIGITS);
    }

    public static function dhaka(?CarbonInterface $date = null): CarbonInterface
    {
        return ($date ? Carbon::instance($date) : Carbon::now())->setTimezone(self::TZ);
    }

    /** "সোমবার, ১২ অক্টোবর ২০২৬" */
    public static function banglaDate(?CarbonInterface $date = null): string
    {
        $d = self::dhaka($date);

        return self::WEEKDAYS[$d->dayOfWeek] . ', ' . self::digits($d->day) . ' ' . self::MONTHS[$d->month - 1] . ' ' . self::digits($d->year);
    }

    /** @return array{bn:string,en:string} */
    public static function greeting(?CarbonInterface $at = null): array
    {
        $h = (int) self::dhaka($at)->format('G');

        return match (true) {
            $h >= 5 && $h < 12  => ['bn' => 'শুভ সকাল', 'en' => 'Good morning'],
            $h >= 12 && $h < 15 => ['bn' => 'শুভ দুপুর', 'en' => 'Good afternoon'],
            $h >= 15 && $h < 18 => ['bn' => 'শুভ বিকাল', 'en' => 'Good afternoon'],
            $h >= 18 && $h < 20 => ['bn' => 'শুভ সন্ধ্যা', 'en' => 'Good evening'],
            default             => ['bn' => 'শুভ রাত্রি', 'en' => 'Good evening'],
        };
    }
}
