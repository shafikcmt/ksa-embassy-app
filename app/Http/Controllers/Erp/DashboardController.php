<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\AgentTransaction;
use App\Models\ErpSetting;
use App\Models\Expense;
use App\Models\PaymentReceipt;
use App\Models\SmartNote;
use App\Services\ErpReportService;
use Illuminate\Http\Request;

/**
 * ERP Suite dashboard — E4 KPIs + E6a enrichment.
 *
 * All reads only. E6a adds reuse-only widgets: a compact Smart Notes widget
 * (existing SmartNote queries, honouring is_private), quick-view cards, and a
 * yearly summary strip. Passenger status is a simple link to the EXISTING search
 * on the main agency dashboard (not duplicated inline). No money math runs here;
 * route access is enforced by page-access:erp.
 */
class DashboardController extends Controller
{
    public function index(Request $request, ErpReportService $reports)
    {
        $agencyId = auth()->user()->agency_id;

        $settings = ErpSetting::forAgency($agencyId)->first();
        $summary  = $reports->dashboardSummary($agencyId);
        $expenses = $reports->expenseTotals($agencyId);

        // Yearly strip — year selector (defaults to the current year).
        $year        = (int) ($request->query('year') ?: now()->year);
        $yearly      = $reports->yearlySummary($agencyId, $year);
        $yearOptions = range(now()->year, now()->year - 5);

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
        ]);
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
