<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Services\ErpReportService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Reports (E4). READ-ONLY.
 *
 * Viewing (index) is open to any access_erp staff — consistent with the Due
 * List / Agent Khata index screens, which already surface the same figures.
 * Exports (PDF/CSV) return a bulk financial file, so they are ADMIN-ONLY,
 * enforced here with the same abort_unless(isAgencyAdmin()) guard used on every
 * money-moving action in the suite.
 *
 * All numbers come from ErpReportService (which only sums verified columns and
 * delegates to the canonical services); this controller performs no money math.
 */
class ReportController extends Controller
{
    public function index(Request $request, ErpReportService $reports)
    {
        $agencyId = auth()->user()->agency_id;
        $filters  = $this->filters($request);

        return view('erp.reports.index', $this->build($agencyId, $filters, $reports) + [
            'filters' => $filters,
        ]);
    }

    public function exportPdf(Request $request, ErpReportService $reports, PdfGeneratorService $pdf)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // bulk financial export: admin-only

        $agencyId = auth()->user()->agency_id;
        $filters  = $this->filters($request);

        $data = $this->build($agencyId, $filters, $reports) + [
            'filters'  => $filters,
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
        ];

        return $pdf->generateFromView('erp.reports.pdf', $data, 'erp-report-' . now()->format('Y-m-d'));
    }

    public function exportCsv(Request $request, ErpReportService $reports): StreamedResponse
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // bulk financial export: admin-only

        $agencyId = auth()->user()->agency_id;
        $filters  = $this->filters($request);
        $data     = $this->build($agencyId, $filters, $reports);

        $filename = 'erp-report-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($data, $filters) {
            $out = fopen('php://output', 'w');

            // Header block
            fputcsv($out, ['ERP Report']);
            fputcsv($out, ['Date range', ($filters['from'] ?: 'begin') . ' to ' . ($filters['to'] ?: 'today')]);
            fputcsv($out, []);

            // Summary
            fputcsv($out, ['Summary']);
            fputcsv($out, ['Collected (in range)', number_format($data['collectedInRange'], 2, '.', '')]);
            fputcsv($out, ['Collected (all-time)', number_format($data['summary']['collected'], 2, '.', '')]);
            fputcsv($out, ['Total Billed', number_format($data['summary']['totalBilled'], 2, '.', '')]);
            fputcsv($out, ['Outstanding Due', number_format($data['summary']['outstandingDue'], 2, '.', '')]);
            fputcsv($out, ['Expenses (in range)', number_format($data['expenses']['rangeTotal'], 2, '.', '')]);
            fputcsv($out, ['Agent Receivable', number_format($data['summary']['agentReceivable'], 2, '.', '')]);
            fputcsv($out, ['Agent Payable', number_format($data['summary']['agentPayable'], 2, '.', '')]);
            fputcsv($out, []);

            // Outstanding dues detail
            fputcsv($out, ['Outstanding Dues']);
            fputcsv($out, ['Type', 'Date', 'Name', 'Passport', 'Billed', 'Paid', 'Due', 'Status']);
            foreach ($data['dues']['rows'] as $r) {
                fputcsv($out, [
                    $r['source_label'], $r['date'], $r['full_name'], $r['passport_no'],
                    number_format($r['billed'], 2, '.', ''),
                    number_format($r['paid'], 2, '.', ''),
                    number_format($r['due'], 2, '.', ''),
                    $r['status'],
                ]);
            }
            fputcsv($out, []);

            // Expenses detail (in range)
            fputcsv($out, ['Expenses (in range)']);
            fputcsv($out, ['Date', 'Category', 'Paid Via', 'Amount', 'Note']);
            foreach ($data['expenses']['rows'] as $e) {
                fputcsv($out, [
                    $e->expense_date->format('d M Y'),
                    $e->categoryLabel(),
                    $e->paidViaLabel() ?? '—',
                    number_format((float) $e->amount, 2, '.', ''),
                    $e->note,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Assemble the read-only report payload once, shared by view + both exports.
     */
    private function build(int $agencyId, array $filters, ErpReportService $reports): array
    {
        return [
            'summary'          => $reports->dashboardSummary($agencyId),
            'collectedInRange' => $reports->collectedInRange($agencyId, $filters['from'], $filters['to']),
            'dues'             => $reports->outstandingDues($agencyId, $filters),
            'expenses'         => $reports->expenseTotals($agencyId, $filters),
            'agents'           => $reports->agentBalances($agencyId),
        ];
    }

    /**
     * Normalize request filters (bookmarkable GET). Only from/to/q are honored.
     */
    private function filters(Request $request): array
    {
        return [
            'from' => $request->query('from') ?: null,
            'to'   => $request->query('to') ?: null,
            'q'    => trim((string) $request->query('q', '')),
        ];
    }
}
