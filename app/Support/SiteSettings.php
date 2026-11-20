<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Public-site (landing / login / register) content that the Super Admin edits
 * from Global Settings → "Website & Marketing". Stored as global rows in the
 * existing `settings` table (agency_id = null) — no schema change.
 */
class SiteSettings
{
    /** key => [default, max length] */
    public const FIELDS = [
        'site_whatsapp_number'  => ['', 30],
        'site_whatsapp_message' => ['Hello, I want to know about VisaDeskPro.', 300],
        'site_phone'            => ['', 40],
        'site_business_hours'   => ['Sat–Thu, 9am–6pm', 100],
        'site_facebook_url'     => ['', 300],
        'site_youtube_url'      => ['', 300],
        'site_meta_pixel_id'    => ['', 30],
        'site_hero_eyebrow'     => ['Built for Saudi recruiting & manpower agencies', 120],
        'site_hero_title'       => ['Prepare KSA embassy files in minutes, not hours', 160],
        'site_hero_highlight'   => ['minutes, not hours', 80],
        'site_hero_lead'        => ['Type the candidate once. VisaDeskPro generates the Application, Forwarding, Agreement and Checklist in the official KSA A4 format — with barcode and photo placed correctly.', 400],
        'site_cta_text'         => ['Start Now — Create Account', 60],
        'site_show_trust'       => ['1', 1],
        'site_trust_1_value'    => ['500+', 20], 'site_trust_1_label' => ['Embassy files generated', 60],
        'site_trust_2_value'    => ['40+', 20],  'site_trust_2_label' => ['Agencies onboard', 60],
        'site_trust_3_value'    => ['4', 20],    'site_trust_3_label' => ['Documents per candidate', 60],
        'site_trust_4_value'    => ['1-click', 20], 'site_trust_4_label' => ['A4 PDF export', 60],
    ];

    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $values = [];
        foreach (self::FIELDS as $key => [$default]) {
            $values[$key] = $default;
        }

        try {
            $rows = Setting::whereNull('agency_id')->whereIn('key', array_keys(self::FIELDS))->pluck('value', 'key');
            foreach ($rows as $key => $value) {
                // Blank = use the default, except the on/off switch and optional contact fields.
                if ($value !== null && ($value !== '' || self::allowsBlank($key))) {
                    $values[$key] = $value;
                }
            }
        } catch (\Throwable $e) {
            // DB not ready (fresh install): fall back to defaults.
        }

        return self::$cache = $values;
    }

    public static function get(string $key): string
    {
        return (string) (self::all()[$key] ?? '');
    }

    /** Digits-only WhatsApp number for wa.me (e.g. 8801XXXXXXXXX). Empty = feature hidden. */
    public static function whatsappNumber(): string
    {
        return preg_replace('/\D/', '', self::get('site_whatsapp_number'));
    }

    public static function whatsappUrl(): ?string
    {
        $n = self::whatsappNumber();

        return $n === '' ? null : 'https://wa.me/'.$n.'?text='.rawurlencode(self::get('site_whatsapp_message'));
    }

    /** Pixel ID: Super Admin setting first, then .env fallback. */
    public static function pixelId(): string
    {
        $id = preg_replace('/\D/', '', self::get('site_meta_pixel_id'));

        return $id !== '' ? $id : preg_replace('/\D/', '', (string) config('services.meta_pixel_id'));
    }

    /** Only http(s) links are ever output. */
    public static function url(string $key): ?string
    {
        $u = trim(self::get($key));

        return preg_match('~^https?://~i', $u) ? $u : null;
    }

    private static function allowsBlank(string $key): bool
    {
        return in_array($key, ['site_whatsapp_number', 'site_phone', 'site_facebook_url', 'site_youtube_url', 'site_meta_pixel_id', 'site_show_trust'], true);
    }

    public static function save(array $input): void
    {
        foreach (self::FIELDS as $key => [$default, $max]) {
            if ($key === 'site_show_trust') {
                Setting::set($key, !empty($input[$key]) ? '1' : '0');
                continue;
            }
            Setting::set($key, mb_substr(trim((string) ($input[$key] ?? '')), 0, $max));
        }
        self::$cache = null;
    }
}
