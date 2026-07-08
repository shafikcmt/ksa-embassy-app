<?php

namespace App\Services;

use Illuminate\Http\Response;
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
     */
    public function generateFromView(string $view, array $data, string $filename): Response
    {
        // _pdf=true lets templates hide screen-only elements (toolbars, flex wrappers)
        $html = view($view, array_merge($data, ['_pdf' => true]))->render();

        if (config('app.debug')) {
            file_put_contents(storage_path('logs/last-pdf-debug.html'), $html);
        }

        $mpdf = $this->makeMpdf();
        $mpdf->SetTitle($filename);
        $mpdf->WriteHTML($html);

        return response(
            $mpdf->Output($filename . '.pdf', 'S'),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"',
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
