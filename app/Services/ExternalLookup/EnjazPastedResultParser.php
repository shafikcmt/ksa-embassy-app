<?php

namespace App\Services\ExternalLookup;

use Illuminate\Http\UploadedFile;

/**
 * Parses a pasted / uploaded Enjaz "Visa Details" result into HR form fields.
 *
 * The public Enjaz (enjazit / MOFA) portal is CAPTCHA-protected and cannot be
 * scraped, so the agency copies the visa result page (text) or saves it as a
 * PDF/HTML and drops it here. We extract the well-known labelled values via
 * regex against both the English and Arabic label text.
 *
 * Returned keys map onto EXISTING form field names only (no new columns):
 *   visa_number, visa_issue_date, full_name_ar, sponsor_id, sponsor_name,
 *   profession_en, nationality, visa_issue_place.
 * sponsor_address / gcc data have no column and are omitted.
 *
 * This service never throws to the caller; failures return found=false.
 */
class EnjazPastedResultParser
{
    /** @return array{found:bool, fields:array<string,string>, message:?string} */
    public function parse(?string $pastedText, ?UploadedFile $file): array
    {
        $text = $this->resolveText($pastedText, $file);

        if ($text instanceof self) {
            // never happens — keeps static analysers calm
        }

        if (is_array($text)) {
            // resolveText returned an error envelope
            return $text;
        }

        $text = $this->normalize($text);

        if (mb_strlen(trim($text)) < 10) {
            return $this->fail('Nothing to parse. Paste the Enjaz visa result text or upload the saved page.');
        }

        $fields = array_filter([
            'visa_number'      => $this->grab($text, [
                'visa issued number', 'visa number', 'visa no', 'رقم التأشيرة', 'رقم تأشيرة',
            ], '/[0-9]{6,}/'),
            'visa_issue_date'  => $this->grab($text, [
                'visa date', 'visa issue date', 'issue date', 'date of issue', 'تاريخ الإصدار', 'تاريخ التأشيرة',
            ]),
            'full_name_ar'     => $this->grabArabic($text, [
                'full name', 'name', 'الاسم', 'اسم العامل', 'اسم الكامل',
            ]),
            'sponsor_id'       => $this->grab($text, [
                'residence number', 'national id number', 'national id', 'residence/national id',
                'sponsor id', 'id number', 'رقم الهوية', 'رقم الإقامة', 'الهوية/الإقامة',
            ], '/[0-9]{8,}/'),
            'sponsor_name'     => $this->grab($text, [
                'sponsor name', 'employer name', 'sponsor', 'اسم الكفيل', 'صاحب العمل',
            ]),
            'profession_en'    => $this->grab($text, [
                'occupation', 'profession', 'job', 'المهنة', 'الوظيفة',
            ]),
            'nationality'      => $this->grab($text, [
                'nationality', 'الجنسية',
            ]),
            'visa_issue_place' => $this->grab($text, [
                'visa issuing authority', 'issuing authority', 'embassy', 'issued at', 'place of issue',
                'جهة الإصدار', 'السفارة', 'مكان الإصدار',
            ]),
            // Delegation Number → New MOFA application ID (hr_profiles.mofa_new).
            'mofa_new'         => $this->grab($text, [
                'delegation number', 'رقم التفويض', 'رقم الوفد',
            ], '/[0-9]{6,}/'),
        ], fn ($v) => $v !== null && $v !== '');

        if (empty($fields)) {
            return $this->fail('Could not read any visa fields. Make sure you pasted the full Enjaz result page.');
        }

        return [
            'found'   => true,
            'fields'  => $fields,
            'message' => null,
        ];
    }

    /**
     * @return string|array Either the extracted text, or an error envelope array.
     */
    private function resolveText(?string $pastedText, ?UploadedFile $file)
    {
        if ($file) {
            $ext = strtolower($file->getClientOriginalExtension());

            if (in_array($ext, ['html', 'htm', 'txt'], true)) {
                return (string) file_get_contents($file->getRealPath());
            }

            if ($ext === 'pdf') {
                $pdfText = $this->extractPdfText($file->getRealPath());
                if ($pdfText === null) {
                    return $this->fail(
                        'PDF text could not be extracted on this server. Open the PDF, copy the text, and paste it into the box instead.'
                    );
                }
                return $pdfText;
            }

            return $this->fail('Unsupported file type. Upload a PDF/HTML export, or paste the text.');
        }

        return (string) ($pastedText ?? '');
    }

    /**
     * Extract text from a PDF with smalot/pdfparser, then hand it to the same
     * normalize()/grab() pipeline used for pasted text (via parse()). Returns
     * null — so the caller shows the graceful "paste the text instead" message —
     * when the parser throws (corrupted PDF) or the PDF is scanned / image-only
     * and yields no extractable text.
     */
    private function extractPdfText(string $path): ?string
    {
        try {
            $text = (new \Smalot\PdfParser\Parser())->parseFile($path)->getText();
        } catch (\Throwable $e) {
            return null; // corrupted / unsupported / encrypted PDF
        }

        // Scanned or image-only PDFs parse fine but contain no real text.
        return trim($text) === '' ? null : $text;
    }

    /** Strip HTML, decode entities, collapse whitespace but keep line breaks. */
    private function normalize(string $raw): string
    {
        $raw = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $raw) ?? $raw;
        $raw = preg_replace('/<\/(td|th|tr|div|p|li)>/i', "\n", $raw) ?? $raw;
        $raw = preg_replace('/<br\s*\/?>/i', "\n", $raw) ?? $raw;
        $text = strip_tags($raw);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n\s*\n+/', "\n", $text) ?? $text;
        return trim($text);
    }

    /**
     * Find the value following any label. When $constrain is given, the value
     * is refined to that sub-pattern (e.g. a run of digits for an ID number).
     */
    private function grab(string $text, array $labels, ?string $constrain = null): ?string
    {
        foreach ($labels as $label) {
            $pattern = '/'.preg_quote($label, '/').'\s*[:\-]?\s*([^\r\n|]+)/iu';
            if (preg_match($pattern, $text, $m)) {
                $value = trim($m[1]);
                $value = preg_split('/\s{2,}/u', $value)[0] ?? $value;
                $value = trim($value);

                if ($constrain && preg_match($constrain, $value, $c)) {
                    return $c[0];
                }
                if (! $constrain && $value !== '' && mb_strlen($value) <= 120) {
                    return $value;
                }
            }
        }
        return null;
    }

    /** Like grab() but returns only the Arabic portion of the matched value. */
    private function grabArabic(string $text, array $labels): ?string
    {
        $value = $this->grab($text, $labels);
        if ($value && preg_match('/[\x{0600}-\x{06FF}][\x{0600}-\x{06FF}\s]+/u', $value, $m)) {
            return trim($m[0]);
        }
        return null;
    }

    private function fail(string $message): array
    {
        return ['found' => false, 'fields' => [], 'message' => $message];
    }
}
