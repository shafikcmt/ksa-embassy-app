<?php

namespace App\Services;

use Illuminate\Http\Response;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

class PdfGeneratorService
{
    private function makeMpdf(array $options = []): Mpdf
    {
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
