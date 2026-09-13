<?php

namespace App\Services\ExternalLookup;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OPTIONAL authenticated Enjaz (business account) visa lookup.
 *
 * Only usable when ENJAZ_BUSINESS_USERNAME / ENJAZ_BUSINESS_PASSWORD are set
 * (see config/services.php → services.enjaz). When credentials are absent the
 * feature stays silently disabled and the app falls back to the paste/parse
 * flow handled by EnjazPastedResultParser.
 *
 * Credentials are read ONLY from config/env — never hardcoded. This service
 * never throws to the caller; failures return ['found' => false, ...].
 */
class EnjazVisaLookupService
{
    private const LOGIN_URL  = 'https://visa.mofa.gov.sa/Account/Login';
    private const SEARCH_URL = 'https://visa.mofa.gov.sa/Visa/Search';
    private const TIMEOUT    = 20;

    public function __construct(
        private ?string $username = null,
        private ?string $password = null,
    ) {
        $this->username = $username ?? config('services.enjaz.username');
        $this->password = $password ?? config('services.enjaz.password');
    }

    /** Feature is only active when both credentials are configured. */
    public function isEnabled(): bool
    {
        return ! empty($this->username) && ! empty($this->password);
    }

    /** @return array{found:bool, fields:array<string,string>, message:?string} */
    public function lookupByVisaNumber(string $visaNumber): array
    {
        if (! $this->isEnabled()) {
            return $this->disabled();
        }

        $visaNumber = trim($visaNumber);
        if ($visaNumber === '') {
            return $this->fail('Visa number is required.');
        }

        try {
            $jar = new CookieJar();

            // 1) GET the login page for any anti-forgery token / cookies.
            $login = Http::withOptions(['cookies' => $jar])
                ->timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => $this->userAgent()])
                ->get(self::LOGIN_URL);

            if (! $login->successful()) {
                return $this->fail('Enjaz portal is unreachable. Use the paste option instead.');
            }

            $token = $this->extractToken($login->body());

            // 2) POST credentials to authenticate the session.
            $auth = Http::withOptions(['cookies' => $jar])
                ->timeout(self::TIMEOUT)
                ->asForm()
                ->withHeaders(['User-Agent' => $this->userAgent(), 'Referer' => self::LOGIN_URL])
                ->post(self::LOGIN_URL, array_filter([
                    '__RequestVerificationToken' => $token,
                    'UserName'                   => $this->username,
                    'Password'                   => $this->password,
                ]));

            if (! $auth->successful()) {
                return $this->fail('Enjaz login failed. Check credentials or use the paste option.');
            }

            // 3) Fetch the visa record.
            $result = Http::withOptions(['cookies' => $jar])
                ->timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => $this->userAgent(), 'Referer' => self::LOGIN_URL])
                ->get(self::SEARCH_URL, ['visaNumber' => $visaNumber]);

            if (! $result->successful()) {
                return $this->fail('Enjaz did not return the visa. Use the paste option.');
            }

            // Reuse the pasted-result parser to read the returned HTML.
            $parsed = (new EnjazPastedResultParser())->parse($result->body(), null);

            return $parsed['found']
                ? $parsed
                : $this->fail('Visa not found on Enjaz. Verify the number or use the paste option.');
        } catch (\Throwable $e) {
            Log::warning('Enjaz visa lookup failed', ['visa' => $visaNumber, 'error' => $e->getMessage()]);
            return $this->fail('Could not reach Enjaz automatically. Use the paste option.');
        }
    }

    private function extractToken(string $html): ?string
    {
        if (preg_match('/name=["\']__RequestVerificationToken["\']\s+[^>]*value=["\']([^"\']+)["\']/i', $html, $m)) {
            return $m[1];
        }
        return null;
    }

    private function userAgent(): string
    {
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
    }

    private function disabled(): array
    {
        return ['found' => false, 'fields' => [], 'message' => null, 'disabled' => true];
    }

    private function fail(string $message): array
    {
        return ['found' => false, 'fields' => [], 'message' => $message];
    }
}
