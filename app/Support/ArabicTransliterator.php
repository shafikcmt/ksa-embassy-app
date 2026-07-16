<?php

namespace App\Support;

/**
 * Offline Latin→Arabic phonetic transliteration — a faithful PHP port of the
 * client-side `translit()` logic in resources/views/agency/hr/_form.blade.php
 * (the "generic"/free-text path used by the Sponsor Name "ع" Generate button).
 *
 * No external package or API. Same TR_MULTI / TR_ONE maps as the JS so browser
 * "Generate Arabic" and any server-side backfill produce identical output.
 */
class ArabicTransliterator
{
    /** Multi-character Latin sequences → Arabic (checked before single chars). */
    private const TR_MULTI = [
        ['sh', 'ش'], ['ch', 'تش'], ['th', 'ث'], ['kh', 'خ'], ['gh', 'غ'],
        ['ph', 'ف'], ['ck', 'ك'], ['oo', 'و'], ['ou', 'و'], ['ee', 'ي'],
        ['aa', 'ا'], ['ll', 'ل'],
    ];

    /**
     * Known-name lookup checked word-by-word BEFORE phonetic transliteration, so
     * well-known Saudi/Arabic names get their correct spelling instead of a rough
     * phonetic guess. Keys are lowercase; the whitespace-delimited word (incl. any
     * hyphen, e.g. "al-otaibi") is looked up as-is. Unknown words (real foreign /
     * company / farm names) fall through to translit()'s phonetic path unchanged.
     *
     * Keep this list in sync with the browser "Generate Arabic" button in
     * resources/views/agency/hr/_form.blade.php if it later reuses the same map.
     */
    private const NAME_MAP = [
        // ── Given names (+ common spelling variants) ──
        'abdullah' => 'عبدالله', 'abdallah' => 'عبدالله', 'abdulla' => 'عبدالله',
        'omar' => 'عمر', 'umar' => 'عمر',
        'khalid' => 'خالد', 'khaled' => 'خالد',
        'mansour' => 'منصور', 'mansoor' => 'منصور', 'mansur' => 'منصور',
        'saad' => 'سعد', 'saeed' => 'سعيد', 'said' => 'سعيد',
        'fahad' => 'فهد', 'fahd' => 'فهد',
        'turki' => 'تركي',
        'mohammed' => 'محمد', 'mohammad' => 'محمد', 'muhammad' => 'محمد',
        'mohamed' => 'محمد', 'muhammed' => 'محمد', 'mohd' => 'محمد',
        'ahmed' => 'أحمد', 'ahmad' => 'أحمد',
        'ali' => 'علي',
        'abdulaziz' => 'عبدالعزيز', 'abdelaziz' => 'عبدالعزيز', 'abdul-aziz' => 'عبدالعزيز',
        'abdulrahman' => 'عبدالرحمن', 'abdurrahman' => 'عبدالرحمن',
        'abdelrahman' => 'عبدالرحمن', 'abdul-rahman' => 'عبدالرحمن',
        // ── Family / tribal surnames (Al- forms) ──
        'al-otaibi' => 'العتيبي', 'al-ghamdi' => 'الغامدي', 'al-shehri' => 'الشهري',
        'al-shahri' => 'الشهري', 'al-zahrani' => 'الزهراني', 'al-qahtani' => 'القحطاني',
        'al-mutairi' => 'المطيري', 'al-dosari' => 'الدوسري', 'al-dossari' => 'الدوسري',
    ];

    /** Single Latin character → Arabic. */
    private const TR_ONE = [
        'a' => 'ا', 'b' => 'ب', 'c' => 'ك', 'd' => 'د', 'e' => 'ي', 'f' => 'ف',
        'g' => 'ج', 'h' => 'ه', 'i' => 'ي', 'j' => 'ج', 'k' => 'ك', 'l' => 'ل',
        'm' => 'م', 'n' => 'ن', 'o' => 'و', 'p' => 'ب', 'q' => 'ق', 'r' => 'ر',
        's' => 'س', 't' => 'ت', 'u' => 'و', 'v' => 'ف', 'w' => 'و', 'x' => 'كس',
        'y' => 'ي', 'z' => 'ز',
    ];

    /**
     * Transliterate a full value. Returns '' for empty; leaves already-Arabic
     * text untouched (mirrors the JS guard), so it never mangles real Arabic.
     */
    public static function translit(?string $value): string
    {
        $s = trim((string) $value);
        if ($s === '') {
            return '';
        }
        // Already contains Arabic → leave as-is.
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $s)) {
            return $s;
        }

        $words = preg_split('/\s+/', $s) ?: [];
        $out = array_filter(array_map(static function (string $w): string {
            // Known-name spelling wins; otherwise fall back to phonetic transliteration.
            return self::NAME_MAP[mb_strtolower($w, 'UTF-8')] ?? self::word($w);
        }, $words), fn ($w) => $w !== '');

        return implode(' ', $out);
    }

    /** Transliterate a single whitespace-delimited word. */
    private static function word(string $w): string
    {
        $lw  = mb_strtolower($w, 'UTF-8');
        $len = strlen($lw); // Latin input is ASCII; byte length is safe here.
        $out = '';
        $i   = 0;

        while ($i < $len) {
            $ch  = $lw[$i];
            $two = substr($lw, $i, 2);

            $hit = null;
            foreach (self::TR_MULTI as [$seq, $ar]) {
                if ($seq === $two) {
                    $hit = $ar;
                    break;
                }
            }
            if ($hit !== null) {
                $out .= $hit;
                $i   += 2;
                continue;
            }

            if (ctype_digit($ch)) {
                $out .= $ch;
                $i++;
                continue;
            }

            $out .= self::TR_ONE[$ch] ?? ($ch === '-' || $ch === '/' ? $ch : '');
            $i++;
        }

        return $out;
    }
}
