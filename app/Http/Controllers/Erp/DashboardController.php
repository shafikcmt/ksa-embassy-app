<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\AgentTransaction;
use App\Models\ErpSetting;
use App\Models\Expense;
use App\Models\PaymentReceipt;
use App\Services\ErpReportService;

/**
 * ERP Suite dashboard — E4.
 *
 * Renders real KPIs from ErpReportService (read-only aggregation over the
 * verified E1–E3 sources) plus a few recent-activity lists. No money math runs
 * here — the controller only reads. Viewing is open to any access_erp staff;
 * route-level access is enforced by page-access:erp.
 */
class DashboardController extends Controller
{
    public function index(ErpReportService $reports)
    {
        $agencyId = auth()->user()->agency_id;

        $settings = ErpSetting::forAgency($agencyId)->first();
        $summary  = $reports->dashboardSummary($agencyId);
        $expenses = $reports->expenseTotals($agencyId);

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
            'settings'        => $settings,
            'summary'         => $summary,
            'byCategory'      => $expenses['byCategory'],
            'recentPayments'  => $recentPayments,
            'recentExpenses'  => $recentExpenses,
            'recentAgentTxns' => $recentAgentTxns,
        ]);
    }
}
