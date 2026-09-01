<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DoubleMofa;
use App\Models\Expense;
use App\Models\ManpowerCompletion;
use App\Models\MofaEntry;
use App\Models\PaymentReceipt;
use App\Models\Stamping;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * ErpReportService — READ-ONLY aggregation for the ERP dashboard & reports (E4).
 *
 * This service NEVER writes money. Every figure it returns is either:
 *   - a SUM of an already-verified column (deliveries.paid_amount /
 *     double_mofas.paid_amount are the E2 ledger-backed caches, proven by the
 *     E2 money-integrity suite), or
 *   - delegated to an existing verified source (AgentKhataService for agent
 *     balances; the same due/unpaid math the Due List already ships).
 *
 * The ONE genuinely-new query is collectedInRange(): the paid_amount caches are
 * all-time only, so "money collected between X and Y" can only come from the
 * payment_receipts ledger. It reuses the EXACT sign convention in
 * ErpPaymentService::recompute() (payment = +amount, reversal = −amount).
 *
 * Tenant safety: every method takes an explicit int $agencyId and every query
 * applies the agency scope BEFORE any SUM/GROUP BY (via the models' forAgency
 * scope or an explicit leading where('agency_id', …)).
 */
class ErpReportService
{
    public function __construct(private AgentKhataService $khata)
    {
    }

    /**
     * Everything the dashboard needs, in one array. Pure composition of the
     * other read-only methods below — no math of its own.
     */
    public function dashboardSummary(int $agencyId): array
    {
        $collected = $this->collectedTotal($agencyId);

        $deliveryBilled   = (float) Delivery::forAgency($agencyId)->sum('total_amount');
        $doubleMofaBilled = (float) DoubleMofa::forAgency($agencyId)->sum('billing_amount');

        $dues     = $this->outstandingDues($agencyId);
        $expenses = $this->expenseTotals($agencyId);
        $agents   = $this->agentBalances($agencyId);

        return [
            'collected'        => $collected,
            'deliveryBilled'   => $deliveryBilled,
            'doubleMofaBilled' => $doubleMofaBilled,
            'totalBilled'      => $deliveryBilled + $doubleMofaBilled,
            'outstandingDue'   => $dues['combinedDue'],
            'deliveryDue'      => $dues['deliveryDue'],
            'doubleMofaDue'    => $dues['doubleMofaDue'],
            'expenseMonth'     => $expenses['month'],
            'expenseAllTime'   => $expenses['allTime'],
            'agentReceivable'  => $agents['receivable'],
            'agentPayable'     => $agents['payable'],
            'agentNet'         => $agents['net'],
            'pendingDelivery'  => $this->pendingDeliveryCount($agencyId),
        ];
    }

    /** Count of deliveries still awaiting fulfilment (manual status, not payment). */
    public function pendingDeliveryCount(int $agencyId): int
    {
        return Delivery::forAgency($agencyId)->where('status', 'pending')->count();
    }

    /**
     * Yearly operational + expense totals for the dashboard "yearly strip".
     * Counts are non-money operational logs; expense is the only money term
     * (already-verified Expense.amount). Everything is agency-scoped first.
     *
     * @return array{year: int, mofa: int, delivery: int, stamping: int, expense: float}
     */
    public function yearlySummary(int $agencyId, int $year): array
    {
        return [
            'year'     => $year,
            'mofa'     => MofaEntry::forAgency($agencyId)->whereYear('mofa_date', $year)->count(),
            'delivery' => Delivery::forAgency($agencyId)->whereYear('delivery_date', $year)->count(),
            'stamping' => Stamping::forAgency($agencyId)->whereYear('stamp_date', $year)->count(),
            'expense'  => (float) Expense::forAgency($agencyId)->whereYear('expense_date', $year)->sum('amount'),
        ];
    }

    /**
     * NON-SENSITIVE per-month (or per-day) operational snapshot for the E6b
     * dashboard summary cards + Daily/Monthly Summary PDFs.
     *
     * Everything here is safe to show ANY access_erp staff — operational counts
     * plus the same money-in / money-out / due figures already surfaced on the
     * module screens and Reports. It deliberately contains NO profit and NO
     * balance (those are computed only in ProfitLossService, behind the E5 gate).
     *
     * `$from`/`$to` are an inclusive date window (a single day when equal). Counts
     * use each module's own record date; `income` reuses the reversal-safe ledger
     * query; `expense` sums the verified amount column; `due` reuses the canonical
     * outstanding-due computation, record-date-scoped to the window (informational
     * — it does not enter any balance math).
     *
     * @return array{mofa:int, doubleMofa:int, stamping:int, manpower:int, delivery:int, pendingDelivery:int, income:float, expense:float, due:float}
     */
    public function monthlyOperational(int $agencyId, string $from, string $to): array
    {
        return [
            'mofa'            => MofaEntry::forAgency($agencyId)->whereBetween('mofa_date', [$from, $to])->count(),
            'doubleMofa'      => DoubleMofa::forAgency($agencyId)->whereBetween('mofa_date', [$from, $to])->count(),
            'stamping'        => Stamping::forAgency($agencyId)->whereBetween('stamp_date', [$from, $to])->count(),
            'manpower'        => ManpowerCompletion::forAgency($agencyId)->whereBetween('completed_date', [$from, $to])->count(),
            'delivery'        => Delivery::forAgency($agencyId)->whereBetween('delivery_date', [$from, $to])->count(),
            'pendingDelivery' => Delivery::forAgency($agencyId)->where('status', 'pending')
                                    ->whereBetween('delivery_date', [$from, $to])->count(),
            'income'          => $this->collectedInRange($agencyId, $from, $to),
            'expense'         => (float) Expense::forAgency($agencyId)->whereBetween('expense_date', [$from, $to])->sum('amount'),
            'due'             => $this->outstandingDues($agencyId, ['from' => $from, 'to' => $to])['combinedDue'],
        ];
    }

    /**
     * 12-month trend series for the E6c dashboard charts. READ-ONLY, agency-scoped.
     *
     * One chronological bucket per month (oldest → newest, ending at $endMonth or
     * the current month). Every figure REUSES a verified per-window helper —
     * collectedInRange (ledger, reversal-safe), the verified Expense.amount column,
     * module counts, and the canonical outstandingDues — so a bucket can never
     * diverge from the totals E4/E5 already prove. Because record-date months are
     * disjoint, Σ(buckets) equals the same helper over the whole window (asserted
     * by the E6c integrity check). Contains NO profit/balance, so it stays entirely
     * outside the E5 (Profit/Loss) gate.
     *
     * `income` doubles as the "Collected" series of the Due Collection chart; it is
     * paired there with `dueRaised` (record-date-scoped billed − paid, same basis
     * as the E6b "Total Due").
     *
     * @return array{labels:string[], mofa:int[], delivery:int[], income:float[], expense:float[], dueRaised:float[]}
     */
    public function twelveMonthSeries(int $agencyId, ?Carbon $endMonth = null): array
    {
        $end = ($endMonth ? $endMonth->copy() : now())->startOfMonth();

        $labels = $mofa = $delivery = $income = $expense = $dueRaised = [];

        for ($i = 11; $i >= 0; $i--) {
            $start  = $end->copy()->subMonthsNoOverflow($i);
            $mStart = $start->toDateString();
            $mEnd   = $start->copy()->endOfMonth()->toDateString();

            $labels[]    = $start->format('M Y');
            $mofa[]      = MofaEntry::forAgency($agencyId)->whereBetween('mofa_date', [$mStart, $mEnd])->count();
            $delivery[]  = Delivery::forAgency($agencyId)->whereBetween('delivery_date', [$mStart, $mEnd])->count();
            $income[]    = $this->collectedInRange($agencyId, $mStart, $mEnd);
            $expense[]   = (float) Expense::forAgency($agencyId)->whereBetween('expense_date', [$mStart, $mEnd])->sum('amount');
            $dueRaised[] = $this->outstandingDues($agencyId, ['from' => $mStart, 'to' => $mEnd])['combinedDue'];
        }

        return compact('labels', 'mofa', 'delivery', 'income', 'expense', 'dueRaised');
    }

    /**
     * All-time collected across both money modules, read from the ledger-backed
     * paid_amount caches (NOT the ledger) so the number is the very same one the
     * per-record screens display. See collectedInRange() for date-bounded sums.
     */
    public function collectedTotal(int $agencyId): float
    {
        return (float) Delivery::forAgency($agencyId)->sum('paid_amount')
            + (float) DoubleMofa::forAgency($agencyId)->sum('paid_amount');
    }

    /**
     * Money collected within [$from, $to] (inclusive by date). The ONE new
     * aggregation in E4: the paid_amount cache cannot answer a date window, so
     * this sums the payment_receipts ledger with the same sign convention as
     * ErpPaymentService (payment = +amount, reversal = −amount). Null bounds are
     * unbounded, so with both null this equals collectedTotal().
     */
    public function collectedInRange(int $agencyId, ?string $from = null, ?string $to = null): float
    {
        $query = PaymentReceipt::where('agency_id', $agencyId); // tenant scope FIRST

        if ($from) {
            $query->whereDate('received_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('received_at', '<=', $to);
        }

        return (float) $query
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'reversal' THEN -amount ELSE amount END), 0) AS net")
            ->value('net');
    }

    /**
     * Outstanding dues across Delivery + Double MOFA. Independently re-derives
     * the SAME numbers the Due List computes (Delivery: total_amount − paid,
     * Double MOFA: billing_amount − paid, both > 0) from the same verified
     * columns — intentionally NOT sharing code with DueListController so that
     * already-proven controller stays untouched. Optional filters: from/to (on
     * the record date) and q (name/passport).
     *
     * @return array{deliveryDue: float, doubleMofaDue: float, combinedDue: float, count: int, rows: Collection}
     */
    public function outstandingDues(int $agencyId, array $filters = []): array
    {
        $from = $filters['from'] ?? null;
        $to   = $filters['to'] ?? null;
        $q    = trim((string) ($filters['q'] ?? ''));

        $deliveryRows = $this->dueRows(
            Delivery::forAgency($agencyId)->whereRaw('total_amount - paid_amount > 0'),
            'delivery_date', $from, $to, $q
        )->map(fn (Delivery $d) => [
            'source'       => 'delivery',
            'source_label' => 'Delivery',
            'date'         => $d->delivery_date->format('d M Y'),
            'date_sort'    => $d->delivery_date->format('Y-m-d'),
            'full_name'    => $d->full_name,
            'passport_no'  => $d->passport_no,
            'billed'       => (float) $d->total_amount,
            'paid'         => (float) $d->paid_amount,
            'due'          => (float) $d->due,           // getDueAttribute
            'status'       => $d->statusLabel(),
        ]);

        $doubleMofaRows = $this->dueRows(
            DoubleMofa::forAgency($agencyId)->whereRaw('billing_amount - paid_amount > 0'),
            'mofa_date', $from, $to, $q
        )->map(fn (DoubleMofa $m) => [
            'source'       => 'double_mofa',
            'source_label' => 'Double MOFA',
            'date'         => $m->mofa_date->format('d M Y'),
            'date_sort'    => $m->mofa_date->format('Y-m-d'),
            'full_name'    => $m->full_name,
            'passport_no'  => $m->passport_no,
            'billed'       => (float) $m->billing_amount,
            'paid'         => (float) $m->paid_amount,
            'due'          => (float) $m->unpaid,         // getUnpaidAttribute
            'status'       => $m->statusLabel(),
        ]);

        $rows = $deliveryRows->concat($doubleMofaRows)->sortByDesc('due')->values();

        $deliveryDue   = (float) $deliveryRows->sum('due');
        $doubleMofaDue = (float) $doubleMofaRows->sum('due');

        return [
            'deliveryDue'   => $deliveryDue,
            'doubleMofaDue' => $doubleMofaDue,
            'combinedDue'   => $deliveryDue + $doubleMofaDue,
            'count'         => $rows->count(),
            'rows'          => $rows,
        ];
    }

    /**
     * Expense figures. `month` and `allTime` mirror ExpenseController::index
     * exactly. `byCategory` is the all-time breakdown. When from/to are given,
     * `rangeTotal` + `rows` cover that window for the Reports screen.
     *
     * @return array{month: float, allTime: float, byCategory: Collection, rangeTotal: float, rows: Collection}
     */
    public function expenseTotals(int $agencyId, array $filters = []): array
    {
        $from = $filters['from'] ?? null;
        $to   = $filters['to'] ?? null;

        $all = Expense::forAgency($agencyId)
            ->with('createdBy:id,name')
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->get();

        $now = now();
        $month = (float) $all
            ->filter(fn ($e) => $e->expense_date->year === $now->year && $e->expense_date->month === $now->month)
            ->sum(fn ($e) => (float) $e->amount);

        $allTime = (float) $all->sum(fn ($e) => (float) $e->amount);

        $byCategory = $all
            ->groupBy('category')
            ->map(fn ($rows) => (float) $rows->sum(fn ($e) => (float) $e->amount))
            ->sortDesc();

        // Date-windowed slice for reports (filtered in PHP off the same set).
        $rows = $all->filter(function ($e) use ($from, $to) {
            $d = $e->expense_date->format('Y-m-d');
            if ($from && $d < $from) return false;
            if ($to && $d > $to) return false;
            return true;
        })->values();

        return [
            'month'      => $month,
            'allTime'    => $allTime,
            'byCategory' => $byCategory,
            'rangeTotal' => (float) $rows->sum(fn ($e) => (float) $e->amount),
            'rows'       => $rows,
        ];
    }

    /**
     * Agent balances snapshot. Delegates the live balance computation to the
     * canonical AgentKhataService (opening_balance + signed-sum) — never
     * re-implements it. Mirrors AgentKhataController::index totals.
     *
     * @return array{balances: Collection, receivable: float, payable: float, net: float}
     */
    public function agentBalances(int $agencyId): array
    {
        $balances = $this->khata->balancesForAgency($agencyId); // keyed by agent_id

        $receivable = (float) $balances->filter(fn ($b) => $b > 0)->sum();
        $payable    = (float) $balances->filter(fn ($b) => $b < 0)->sum(); // negative

        return [
            'balances'   => $balances,
            'receivable' => $receivable,
            'payable'    => abs($payable),
            'net'        => $receivable + $payable,
        ];
    }

    /**
     * Shared date/search filtering for the due queries. Returns the eager rows.
     */
    private function dueRows($query, string $dateColumn, ?string $from, ?string $to, string $q): Collection
    {
        if ($from) {
            $query->whereDate($dateColumn, '>=', $from);
        }
        if ($to) {
            $query->whereDate($dateColumn, '<=', $to);
        }
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('full_name', 'like', "%{$q}%")
                ->orWhere('passport_no', 'like', "%{$q}%"));
        }

        return $query->get();
    }
}
