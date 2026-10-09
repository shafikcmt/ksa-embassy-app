<?php

namespace App\Services;

use Illuminate\Http\Response;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class PdfGeneratorService
{
    private function makeMpdf(array $options = []): Mpdf
    {
        // Register a custom 'ksaroboto' font family (Medium=Regular, Bold) IN ADDITION
        // to mPDF's bundled fonts (dejavusans/freesans stay untouched, still used by
        // page 1 and all Arabic text). Only pages 2-4 (forwarding letter, employment
        // agreement, checklist) opt into 'ksaroboto' via their own scoped CSS.
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs      = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData          = $defaultFontConfig['fontdata'];

        $defaults = [
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'margin_top'        => 10,
            'margin_right'      => 10,
            'margin_bottom'     => 10,
            'margin_left'       => 10,
            'tempDir'           => storage_path('app/mpdf-tmp'),
            'autoScriptToLang'  => true,
            'autoLangToFont'    => true,
            'default_font'      => 'dejavusans',
            'fontDir'           => array_merge($fontDirs, [public_path('fonts')]),
            'fontdata'          => $fontData + [
                'ksaroboto' => [
                    'R' => 'Roboto-Medium.ttf',
                    'B' => 'Roboto-Bold.ttf',
                ],
                // Roboto Black — the heavy headline values on the page-1
                // application form (names, dates, passport/visa numbers).
                'ksarobotoblack' => [
                    'R' => 'Roboto-Black.ttf',
                    'B' => 'Roboto-Black.ttf',
                ],
            ],
        ];

        // Ensure temp directory exists
        if (! is_dir(storage_path('app/mpdf-tmp'))) {
            mkdir(storage_path('app/mpdf-tmp'), 0755, true);
        }

        return new Mpdf(array_merge($defaults, $options));
    }

    /**
     * Generate a single-document PDF from a Blade view.
     *
     * $inline defaults to false — every existing caller keeps the original
     * "attachment" (download) behaviour untouched. Passing true sends
     * Content-Disposition: inline so the PDF opens in a new browser tab instead
     * (used by the Credit Voucher print action).
     *
     * $options (optional) are merged over the mPDF defaults — e.g. the Medical
     * Summary passes format A4-L + a footer margin. Existing callers pass none.
     */
    public function generateFromView(string $view, array $data, string $filename, bool $inline = false, array $options = []): Response
    {
        // The Embassy List reference uses US Letter; other documents keep A4.
        if ($view === 'prints.embassy-list') {
            $options = array_replace([
                'format' => 'Letter',
                'margin_top' => 9.525,
                'margin_left' => 12.573,
                'margin_right' => 12.065,
                'margin_bottom' => 12.7,
            ], $options);
        }

        // _pdf=true lets templates hide screen-only elements (toolbars, flex wrappers)
        $html = view($view, array_merge($data, ['_pdf' => true]))->render();

        if (config('app.debug')) {
            file_put_contents(storage_path('logs/last-pdf-debug.html'), $html);
        }

        $mpdf = $this->makeMpdf($options);
        $mpdf->SetTitle($filename);
        $mpdf->WriteHTML($html);

        $disposition = $inline ? 'inline' : 'attachment';

        return response(
            $mpdf->Output($filename . '.pdf', 'S'),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $filename . '.pdf"',
            ]
        );
    }

    /**
     * Opt-in variant of generateFromView() for long tables (MOFA summary only).
     *
     * mPDF refuses any single WriteHTML() string longer than pcre.backtrack_limit (PHP
     * default 1,000,000 bytes) and lays out a whole <table> in memory, so one huge table
     * fails with a 500. Here $view is rendered once as the document shell, with
     * $data['_rowsMarker'] = $marker where its rows belong; the shell head, each HTML
     * string from $chunks (complete tables, rendered lazily) and the shell tail are then
     * written as separate WriteHTML() calls into one document. CSS read from the head
     * applies to every chunk. No PHP runtime limits are changed.
     *
     * @param  iterable<string>  $chunks
     */
    public function generateChunkedFromView(string $view, array $data, string $marker, iterable $chunks, string $filename, bool $inline = false, array $options = []): Response
    {
        $shell = view($view, array_merge($data, ['_pdf' => true, '_rowsMarker' => $marker]))->render();
        $parts = explode($marker, $shell);
        if (count($parts) !== 2) {
            throw new \LogicException("View [{$view}] must output the rows marker exactly once.");
        }

        $mpdf = $this->makeMpdf($options);
        $mpdf->SetTitle($filename);
        $mpdf->WriteHTML($parts[0]);
        foreach ($chunks as $html) {
            $mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
        }
        $mpdf->WriteHTML($parts[1], HTMLParserMode::HTML_BODY);

        return response(
            $mpdf->Output($filename . '.pdf', 'S'),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '.pdf"',
            ]
        );
    }

    /**
     * Generate a multi-page PDF from multiple Blade views (full-file).
     */
    public function generateMultiPage(array $views, array $data, string $filename): Response
    {
        $pdfData = array_merge($data, ['_pdf' => true]);

        $mpdf = $this->makeMpdf();
        $mpdf->SetTitle($filename);

        foreach ($views as $index => $view) {
            if ($index > 0) {
                $mpdf->AddPage();
            }
            $html = view($view, $pdfData)->render();
            $mpdf->WriteHTML($html);
        }

        return response(
            $mpdf->Output($filename . '.pdf', 'S'),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"',
            ]
        );
    }
}
