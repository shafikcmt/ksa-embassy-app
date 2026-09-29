<?php

namespace App\Support;

/**
 * Single source of truth for the look of the ERP summary PDFs (Medical, MOFA,
 * Visa Stamping, BMET): document badges, per-module status colours and the
 * table sizing each module needs to fit its columns on one A4 row.
 *
 * Colours are plain hex pairs [background, text] because mPDF has no CSS
 * variables; the shared partials in resources/views/prints/partials/erp-summary
 * read them from here.
 */
final class ErpPrintTheme
{
    public const PRIMARY      = '#3b82f6';
    public const PRIMARY_DARK = '#1e40af';

    /** Unknown / missing statuses fall back to neutral gray. */
    public const NEUTRAL = ['#f3f4f6', '#374151'];

    /**
     * Per-document settings.
     *   title  – large page title
     *   badge  – short label in the header's document badge
     *   tone   – [background, text] of that badge
     *   noun   – used in "No … records found."
     *   body / head – table font sizes (pt) that keep every column on one line
     */
    public const DOCS = [
        'medical'  => ['title' => 'Medical Summary',        'badge' => 'Medical Summary', 'tone' => ['#d1fae5', '#065f46'], 'noun' => 'medical',        'body' => 8,   'head' => 7.5],
        'mofa'     => ['title' => 'MOFA Summary',           'badge' => 'MOFA Summary',    'tone' => ['#dbeafe', '#1e40af'], 'noun' => 'MOFA',           'body' => 7,   'head' => 6.5],
        'stamping' => ['title' => 'Visa Stamping Summary',  'badge' => 'Visa Stamping',   'tone' => ['#fed7aa', '#92400e'], 'noun' => 'visa stamping',  'body' => 7,   'head' => 6.5],
        'bmet'     => ['title' => 'BMET Clearance Summary', 'badge' => 'BMET Clearance',  'tone' => ['#fecaca', '#991b1b'], 'noun' => 'BMET clearance', 'body' => 9,   'head' => 8.5],
    ];

    /** Status badge colours per module, keyed by the stored status value. */
    public const STATUS = [
        'medical' => [
            'pending'      => ['#fef3c7', '#92400e'],
            'process'      => ['#dbeafe', '#1e40af'],
            'under_review' => ['#ede9fe', '#5b21b6'],
            'fit'          => ['#d1fae5', '#065f46'],
            'unfit'        => ['#fee2e2', '#991b1b'],
            'expired'      => ['#f3f4f6', '#374151'],
        ],
        'mofa' => [
            'active'     => ['#d1fae5', '#065f46'],
            'expiring'   => ['#fef3c7', '#92400e'],
            'expired'    => ['#fee2e2', '#991b1b'],
            'processing' => ['#dbeafe', '#1e40af'],
        ],
        'stamping' => [
            'pending'    => ['#fef3c7', '#92400e'],
            'processing' => ['#fedba8', '#b45309'],
            'completed'  => ['#dbeafe', '#1e40af'],
            'stamped'    => ['#d1fae5', '#065f46'],
            'expired'    => ['#fee2e2', '#991b1b'],
            'rejected'   => ['#f3f4f6', '#374151'],
        ],
        'bmet' => [
            'pending' => ['#fef3c7', '#92400e'],
            'cleared' => ['#d1fae5', '#065f46'],
            'expired' => ['#fee2e2', '#991b1b'],
            'hold'    => ['#fecaca', '#7c2d12'],
        ],
    ];

    /**
     * mPDF constructor options shared by all four summaries: A4 (landscape by
     * default), 12 mm page margins, and a bottom margin tall enough for the
     * branded footer. Passed as PdfGeneratorService::generateFromView() $options.
     */
    public static function mpdfOptions(string $orientation = 'landscape'): array
    {
        return [
            'format'        => $orientation === 'portrait' ? 'A4' : 'A4-L',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 12,
            'margin_bottom' => 20,
            'margin_footer' => 6,
        ];
    }

    public static function doc(string $module): array
    {
        return self::DOCS[$module];
    }

    /** [background, text] for a module's status (neutral gray when unknown). */
    public static function status(string $module, ?string $status): array
    {
        return self::STATUS[$module][$status ?? ''] ?? self::NEUTRAL;
    }

    /** Up to three initials for the logo circle when an agency has no print logo. */
    public static function initials(?string $name): string
    {
        $words = preg_split('/\s+/', trim(preg_replace('/\(.*?\)/', '', (string) $name))) ?: [];
        $letters = array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_filter($words));

        return implode('', array_slice($letters, 0, 3)) ?: 'VD';
    }

    /** dd-MMM-yyyy with non-breaking hyphens so a date never splits across lines. */
    public static function date(?\DateTimeInterface $date): string
    {
        return $date ? str_replace('-', "\u{2011}", $date->format('d-M-Y')) : '';
    }
}
