<?php

namespace App\Http\Controllers\Erp\Concerns;

use App\Services\PdfGeneratorService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared "Print" behaviour for the ERP list modules (E7a).
 *
 * One route per module (erp.<module>.print) now serves BOTH:
 *   • default      → an on-screen A4 preview whose toolbar fires window.print()
 *                    (the browser's native dialog, "Save as PDF" included), the
 *                    same flow as the HR application + Embassy List print pages.
 *   • ?download=1  → the SAME view piped through mPDF as a file download.
 *
 * The shared erp.print.list template hides its screen-only toolbar/wrapper when
 * $_pdf is set (via PdfGeneratorService), so browser preview, print preview and
 * the downloaded PDF stay visually identical. No extra routes are introduced —
 * the module's existing "Print" button (target="_blank") now lands on a preview.
 */
trait RendersPrintableList
{
    protected function respondPrintableList(
        PdfGeneratorService $pdf,
        array $data,
        string $filename,
        string $backRoute
    ): Response|View {
        // Explicit file download (toolbar "Download PDF" link) → real mPDF file.
        if (request()->boolean('download')) {
            return $pdf->generateFromView('erp.print.list', $data, $filename);
        }

        // Default → on-screen preview (Print button uses the browser print dialog).
        return view('erp.print.list', $data + [
            '_downloadUrl' => request()->fullUrlWithQuery(['download' => 1]),
            '_backUrl'     => route($backRoute),
        ]);
    }
}
