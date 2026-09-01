<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePlUnlocked;
use App\Models\AgentTransaction;
use App\Models\ErpSetting;
use App\Models\Expense;
use App\Models\PaymentReceipt;
use App\Models\SmartNote;
use App\Services\ErpReportService;
use App\Services\PdfGeneratorService;
use App\Services\ProfitLossService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Suite dashboard — E4 KPIs + E6a enrichment + E6b monthly summary.
 *
 * All reads only. E6a adds reuse-only widgets: a compact Smart Notes widget
 * (existing SmartNote queries, honouring is_private), quick-view cards, and a
 * yearly summary strip. E6b adds This-month / Previous-month summary cards and
 * three exports (Daily/Monthly Summary PDF + Backup CSV).
 *
 * SECURITY (E6b): operational counts + income/expense/due are visible to any
 * access_erp staff. Profit/Loss and Starting/Ending Balance are OWNER-ONLY —
 * computed and sent to the view ONLY when the user is an agency admin AND P/L is
 * unlocked, reusing the SAME gate as the P/L screen (isAgencyAdmin +
 * EnsurePlUnlocked::isAccessible). When locked, the sensitive figures are never
 * computed; the view shows an "Unlock in Profit/Loss" placeholder. The gate
 * logic itself is untouched — only reused. Passenger status is a simple link to
 * the EXISTING search on the main agency dashboard.
 */
class DashboardController extends Controller
{
    public function index(Request $request, ErpReportService $reports, ProfitLossService $pl)
    {
        $agencyId = auth()->user()->agency_id;

        $settings = ErpSetting::forAgency($agencyId)->first();
        $summary  = $reports->dashboardSummary($agencyId);
        $expenses = $reports->expenseTotals($agencyId);

        // Yearly strip — year selector (defaults to the current year).
        $year        = (int) ($request->query('year') ?: now()->year);
        $yearly      = $reports->yearlySummary($agencyId, $year);
        $yearOptions = range(now()->year, now()->year - 5);

        // E6b — owner-only gate, REUSED exactly as the P/L screen applies it.
        // Sensitive month figures are only computed when this is true.
        $canSeeProfit = auth()->user()->isAgencyAdmin()
            && EnsurePlUnlocked::isAccessible($settings, $request);

        $thisMonth = now()->startOfMonth();
        $prevMonth = now()->subMonthNoOverflow()->startOfMonth();

        // Recent-activity lists — simple agency-scoped reads, no aggregation.
        $recentPayments = PaymentReceipt::where('agency_id', $agencyId)
            ->with(['payable', 'receivedBy:id,name'])
            ->orderByDesc('received_at')->orderByDesc('id')
            ->limit(6)->get();

        $recentExpenses = Expense::forAgency($agencyId)
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->limit(6)->get();

        $recentAgentTxns = AgentTransaction::forAgency($agencyId)
            ->with(['agent:id,name'])
            ->orderByDesc('txn_date')->orderByDesc('id')
            ->limit(6)->get();

        return view('erp.dashboard', [
            'settings'         => $settings,
            'summary'          => $summary,
            'byCategory'       => $expenses['byCategory'],
            'recentPayments'   => $recentPayments,
            'recentExpenses'   => $recentExpenses,
            'recentAgentTxns'  => $recentAgentTxns,
            'yearly'           => $yearly,
            'yearOptions'      => $yearOptions,
            // E6a widgets
            'notesWidget'      => $this->notesWidget($agencyId),
            // E6b monthly summary
            'canSeeProfit'     => $canSeeProfit,
            'thisMonthCard'    => $this->monthCard($agencyId, $thisMonth, $reports, $pl, $canSeeProfit),
            'prevMonthCard'    => $this->monthCard($agencyId, $prevMonth, $reports, $pl, $canSeeProfit),
            // E6c trend charts (operational + income/expense/due — no profit/balance,
            // so no E5 gate involvement; rendered client-side via Chart.js).
            'charts'           => $reports->twelveMonthSeries($agencyId),
        ]);
    }

    /**
     * Assemble one month summary card. Operational block is always built; the
     * SENSITIVE profit/balance block is built ONLY when $canSeeProfit — so when
     * the caller is locked, ProfitLossService::monthBalances is never even called
     * and no owner-only number reaches the view.
     *
     * @return array{label:string, month:string, ops:array, profit:?float, starting:?float, ending:?float}
     */
    private function monthCard(int $agencyId, Carbon $start, ErpReportService $reports, ProfitLossService $pl, bool $canSeeProfit): array
    {
        $end = $start->copy()->endOfMonth();

        $card = [
            'label'    => $start->format('F Y'),
            'month'    => $start->format('Y-m'),
            'ops'      => $reports->monthlyOperational($agencyId, $start->toDateString(), $end->toDateString()),
            'profit'   => null,
            'starting' => null,
            'ending'   => null,
        ];

        if ($canSeeProfit) {
            $b = $pl->monthBalances($agencyId, $start->toDateString(), $end->toDateString());
            $card['profit']   = $b['profit'];
            $card['starting'] = $b['starting'];
            $card['ending']   = $b['ending'];
        }

        return $card;
    }

    /**
     * Daily Summary PDF — operational + income/expense/due for one day, NO profit.
     * Admin-only (suite-wide money-action invariant), but NOT pl-gated because it
     * carries no owner-only figures.
     */
    public function dailySummaryPdf(Request $request, ErpReportService $reports, PdfGeneratorService $pdf)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $agencyId = auth()->user()->agency_id;
        $day = $this->dayFrom($request);

        return $pdf->generateFromView('erp.summary.daily', [
            'ops'       => $reports->monthlyOperational($agencyId, $day->toDateString(), $day->toDateString()),
            'day'       => $day,
            'agency'    => auth()->user()->agency,
            'generated' => now(),
        ], 'daily-summary-' . $day->toDateString());
    }

    /**
     * Monthly Summary PDF — full month card INCLUDING profit + Starting/Ending
     * balance. Admin-only AND behind `pl-unlocked` at the route, so monthBalances
     * only ever runs with a live unlock (or when no code is set).
     */
    public function monthlySummaryPdf(Request $request, ErpReportService $reports, ProfitLossService $pl, PdfGeneratorService $pdf)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $agencyId = auth()->user()->agency_id;
        $start = $this->monthFrom($request);
        $end   = $start->copy()->endOfMonth();

        return $pdf->generateFromView('erp.summary.monthly', [
            'ops'       => $reports->monthlyOperational($agencyId, $start->toDateString(), $end->toDateString()),
            'bal'       => $pl->monthBalances($agencyId, $start->toDateString(), $end->toDateString()),
            'month'     => $start,
            'agency'    => auth()->user()->agency,
            'generated' => now(),
        ], 'monthly-summary-' . $start->format('Y-m'));
    }

    /**
     * Backup CSV — a 12-month financial backup for one year (operational counts +
     * income/expense/due + profit + Starting/Ending balance per month). Admin-only
     * AND behind `pl-unlocked` (it carries the owner-only profit/balance columns).
     */
    public function backupCsv(Request $request, ErpReportService $reports, ProfitLossService $pl): StreamedResponse
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $agencyId = auth()->user()->agency_id;
        $year = (int) ($request->query('year') ?: now()->year);

        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth();
            $end   = $start->copy()->endOfMonth();
            $ops   = $reports->monthlyOperational($agencyId, $start->toDateString(), $end->toDateString());
            $bal   = $pl->monthBalances($agencyId, $start->toDateString(), $end->toDateString());
            $rows[] = [$start, $ops, $bal];
        }

        $filename = 'erp-backup-' . $year . '.csv';

        return response()->streamDownload(function () use ($rows, $year) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ERP Financial Backup', $year]);
            fputcsv($out, []);
            fputcsv($out, [
                'Month', 'MOFA', 'Double MOFA', 'Stamping', 'Manpower', 'Delivery',
                'Income', 'Expense', 'Due', 'Profit', 'Starting Balance', 'Ending Balance',
            ]);
            foreach ($rows as [$start, $ops, $bal]) {
                fputcsv($out, [
                    $start->format('M Y'),
                    $ops['mofa'], $ops['doubleMofa'], $ops['stamping'], $ops['manpower'], $ops['delivery'],
                    number_format($ops['income'], 2, '.', ''),
                    number_format($ops['expense'], 2, '.', ''),
                    number_format($ops['due'], 2, '.', ''),
                    number_format($bal['profit'], 2, '.', ''),
                    number_format($bal['starting'], 2, '.', ''),
                    number_format($bal['ending'], 2, '.', ''),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Resolve a single day from ?date=Y-m-d (defaults to today; invalid → today). */
    private function dayFrom(Request $request): Carbon
    {
        try {
            return $request->query('date')
                ? Carbon::createFromFormat('Y-m-d', $request->query('date'))->startOfDay()
                : now()->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    /** Resolve a month start from ?month=Y-m (defaults to this month; invalid → this month). */
    private function monthFrom(Request $request): Carbon
    {
        try {
            return $request->query('month')
                ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    /**
     * Compact Smart Notes widget: Today / Pinned / upcoming Reminders.
     *
     * Honours is_private — a private note is only visible to its author, so the
     * widget shows agency notes that are either not private OR owned by the
     * current user. Read-only; reuses the existing SmartNote query patterns.
     *
     * @return array{today: Collection, pinned: Collection, reminders: Collection}
     */
    private function notesWidget(int $agencyId): array
    {
        $userId = auth()->id();

        $base = fn () => SmartNote::forAgency($agencyId)
            ->whereNull('archived_at')
            ->where(fn ($q) => $q->where('is_private', false)->orWhere('user_id', $userId));

        $today = $base()
            ->whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')->limit(5)->get();

        $pinned = $base()
            ->where('pinned', true)
            ->orderByDesc('updated_at')->limit(5)->get();

        $reminders = $base()
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '>=', now())
            ->orderBy('reminder_at')->limit(5)->get();

        return compact('today', 'pinned', 'reminders');
    }
}
