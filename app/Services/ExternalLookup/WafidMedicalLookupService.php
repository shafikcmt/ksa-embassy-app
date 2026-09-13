<?php

namespace App\Services\ExternalLookup;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wafid (GCC medical / GCCHMC) status lookup.
 *
 * Best-effort HTML scrape: Wafid's public "medical status search" page is a
 * Django app protected by a CSRF token (and, at times, throttling / captcha).
 * We GET the page first to obtain the session cookie + csrfmiddlewaretoken,
 * then POST the search. The result HTML is parsed defensively.
 *
 * This service NEVER throws to the caller — any network / parse / captcha
 * problem returns ['found' => false, 'message' => ...] so the HR form keeps
 * working and the user can fill the medical fields by hand.
 *
 * Nothing here persists data or touches the DB; it is a pure lookup helper.
 */
class WafidMedicalLookupService
{
    private const SEARCH_URL = 'https://wafid.com/en/medical-status-search/';
    private const TIMEOUT    = 15;

    /**
     * @return array{found:bool, medical_fit:?bool, status_text:?string, medical_date:?string, medical_center:?string, gcc_slip:?string, message:?string}
     */
    public function lookup(string $passportNo, string $nationality): array
    {
        $passportNo  = trim($passportNo);
        $nationality = trim($nationality);

        if ($passportNo === '' || $nationality === '') {
            return $this->notFound('Passport number and nationality are required.');
        }

        try {
            $jar = new CookieJar();

            // 1) GET the search page to seed cookies + read the CSRF token.
            $page = Http::withOptions(['cookies' => $jar])
                ->timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => $this->userAgent()])
                ->get(self::SEARCH_URL);

            if (! $page->successful()) {
                return $this->notFound('Wafid is not reachable right now. Please try again or enter the medical result manually.');
            }

            $token = $this->extractCsrfToken($page->body(), $jar);

            // 2) POST the search. Field names mirror the public Wafid form.
            $response = Http::withOptions(['cookies' => $jar])
                ->timeout(self::TIMEOUT)
                ->asForm()
                ->withHeaders([
                    'User-Agent'   => $this->userAgent(),
                    'Referer'      => self::SEARCH_URL,
                    'Origin'       => 'https://wafid.com',
                    'X-CSRFToken'  => $token ?? '',
                ])
                ->post(self::SEARCH_URL, array_filter([
                    'csrfmiddlewaretoken' => $token,
                    'search_type'         => 'passport',
                    'passport_number'     => $passportNo,
                    'nationality'         => $nationality,
                ]));

            if (! $response->successful()) {
                return $this->notFound('Wafid returned an error. Please enter the medical result manually.');
            }

            return $this->parse($response->body(), $passportNo);
        } catch (\Throwable $e) {
            Log::warning('Wafid medical lookup failed', [
                'passport' => $passportNo,
                'error'    => $e->getMessage(),
            ]);

            return $this->notFound('Could not reach Wafid automatically. Please enter the medical result manually.');
        }
    }

    /**
     * Pull the Django csrfmiddlewaretoken from the page HTML, falling back to
     * the csrftoken cookie value.
     */
    private function extractCsrfToken(string $html, CookieJar $jar): ?string
    {
        if (preg_match('/name=["\']csrfmiddlewaretoken["\']\s+value=["\']([^"\']+)["\']/i', $html, $m)) {
            return $m[1];
        }

        foreach ($jar->toArray() as $cookie) {
            if (($cookie['Name'] ?? '') === 'csrftoken') {
                return $cookie['Value'] ?? null;
            }
        }

        return null;
    }

    /**
     * Defensively parse the result page. Wafid's markup changes over time, so
     * we look for labelled values and common status keywords rather than a
     * fixed DOM path. Missing pieces simply come back null.
     */
    private function parse(string $html, string $passportNo): array
    {
        $text = $this->htmlToText($html);

        // Obvious "no record" signals.
        if (preg_match('/no\s+(record|result|data)\s+found|not\s+found|invalid/i', $text)) {
            return $this->notFound('No medical record found on Wafid for that passport / nationality.');
        }

        $statusText = $this->grab($text, ['medical status', 'status', 'result', 'fitness']);
        $medicalFit = $this->interpretFitness($statusText ?? $text);

        return [
            'found'          => $medicalFit !== null || $statusText !== null,
            'medical_fit'    => $medicalFit,
            'status_text'    => $statusText,
            'medical_date'   => $this->normalizeDate($this->grab($text, ['examination date', 'medical date', 'test date', 'date'])),
            'medical_center' => $this->grab($text, ['medical center', 'medical centre', 'clinic', 'center name']),
            'gcc_slip'       => $this->grab($text, ['gcc slip', 'slip number', 'slip no', 'reference number', 'gcchmc']),
            'message'        => $medicalFit === null && $statusText === null
                ? 'Wafid responded but no status could be read. Please verify on wafid.com and enter manually.'
                : null,
        ];
    }

    /** Map free-text status to the boolean medical_fit column (null = unknown). */
    private function interpretFitness(string $s): ?bool
    {
        if (preg_match('/\bunfit\b|not\s*fit|rejected|failed/i', $s)) {
            return false;
        }
        if (preg_match('/\bfit\b|passed|eligible|valid/i', $s)) {
            return true;
        }
        return null;
    }

    /**
     * Grab the value that follows any of the given labels, e.g.
     * "Medical Center: Al Noor Clinic" → "Al Noor Clinic".
     */
    private function grab(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $pattern = '/'.preg_quote($label, '/').'\s*[:\-]?\s*(.+)/i';
            if (preg_match($pattern, $text, $m)) {
                $value = trim(preg_replace('/\s+/', ' ', $m[1]));
                // Stop at the next label-ish token to avoid swallowing the rest.
                $value = preg_split('/\s{2,}|\||•/', $value)[0] ?? $value;
                $value = trim($value);
                if ($value !== '' && mb_strlen($value) <= 120) {
                    return $value;
                }
            }
        }
        return null;
    }

    /** Normalise a date-ish string to Y-m-d when possible; else return as-is. */
    private function normalizeDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html) ?? $html;
        $text = strip_tags(preg_replace('/<\/(td|th|tr|div|p|li|br)>/i', ' : ', $html) ?? $html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/[ \t]+/', ' ', $text) ?? $text);
    }

    private function userAgent(): string
    {
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
    }

    private function notFound(string $message): array
    {
        return [
            'found'          => false,
            'medical_fit'    => null,
            'status_text'    => null,
            'medical_date'   => null,
            'medical_center' => null,
            'gcc_slip'       => null,
            'message'        => $message,
        ];
    }
}
