<?php

namespace App\Services;

use App\Models\AgentTransaction;
use App\Models\ErpSetting;
use Illuminate\Support\Carbon;

/**
 * ProfitLossService — READ-ONLY, cash-basis Profit/Loss (E5, owner-only).
 *
 * Formula (user-defined, cash basis):
 *   Revenue = Delivery collected + Double MOFA collected   (payment_receipts only)
 *   Cost    = Expenses + agent payouts (money OUT to agents)
 *   Profit  = Revenue − Cost
 *
 * Double-count safety is STRUCTURAL: revenue is delegated entirely to
 * ErpReportService (which sums ONLY the payment_receipts ledger and never opens
 * agent_transactions), so Agent Khata credits/debits cannot leak into revenue.
 * Cost reads `expenses` + `agent_transactions` (disjoint tables; expenses has no
 * agent-commission category), so the two cost terms cannot overlap either.
 *
 * Explicitly EXCLUDED from the profit figure: Agent Khata credits (agent
 * repaying/settling — not new income), opening_balance (shown as context only),
 * and outstanding dues (not collected yet → not counted on cash basis).
 *
 * Reversal-aware: revenue is already reversal-safe (the ledger sum subtracts
 * reversals). The agent-payout term is made reversal-safe here — see
 * agentPayoutCost().
 */
class ProfitLossService
{
    public function __construct(private ErpReportService $reports)
    {
    }

    /**
     * Full P/L for a period. Null $from/$to = all-time.
     *
     * @return array{revenue: float, expenseCost: float, agentPayoutCost: float, totalCost: float, profit: float, openingBalance: float, from: ?string, to: ?string}
     */
    public function summary(int $agencyId, ?string $from = null, ?string $to = null): array
    {
        // Revenue — cash collected in the window. Reuses the E4 ledger query;
        // never touches agent_transactions.
        $revenue = $this->reports->collectedInRange($agencyId, $from, $to);

        // Cost term 1 — expenses in the window (rangeTotal == all-time when unbounded).
        $expenseCost = (float) $this->reports->expenseTotals($agencyId, [
            'from' => $from, 'to' => $to,
        ])['rangeTotal'];

        // Cost term 2 — money actually paid OUT to agents (reversal-aware).
        $agentPayoutCost = $this->agentPayoutCost($agencyId, $from, $to);

        $totalCost = round($expenseCost + $agentPayoutCost, 2);
        $revenue   = round($revenue, 2);

        return [
            'revenue'         => $revenue,
            'expenseCost'     => round($expenseCost, 2),
            'agentPayoutCost' => $agentPayoutCost,
            'totalCost'       => $totalCost,
            'profit'          => round($revenue - $totalCost, 2),
            'openingBalance'  => (float) (ErpSetting::forAgency($agencyId)->value('opening_balance') ?? 0),
            'from'            => $from,
            'to'              => $to,
        ];
    }

    /**
     * SENSITIVE month figures for the E6b dashboard cards + Monthly Summary PDF:
     * the month's cash-basis profit and the running Starting / Ending balance.
     *
     * This is owner-only data — callers MUST gate it exactly like the P/L screen
     * (isAgencyAdmin AND EnsurePlUnlocked::isAccessible). It performs no new money
     * math: it composes summary() over disjoint date ranges only.
     *
     *   startingBalance = openingBalance + profit(all-time up to the day BEFORE the
     *                     month) — i.e. the carried-forward balance entering the month.
     *   endingBalance   = startingBalance + profit(this month).
     *
     * Because cash-basis profit is additive over disjoint date windows (revenue,
     * expense and agent-payout terms are each date-summed), this is internally
     * consistent: one month's ending balance equals the next month's starting
     * balance, and endingBalance == openingBalance + profit(all-time up to month end).
     *
     * @return array{profit: float, starting: float, ending: float}
     */
    public function monthBalances(int $agencyId, string $monthStart, string $monthEnd): array
    {
        $priorTo = Carbon::parse($monthStart)->subDay()->toDateString();
        $opening = (float) (ErpSetting::forAgency($agencyId)->value('opening_balance') ?? 0);

        $priorProfit = $this->summary($agencyId, null, $priorTo)['profit'];
        $monthProfit = $this->summary($agencyId, $monthStart, $monthEnd)['profit'];

        $starting = round($opening + $priorProfit, 2);

        return [
            'profit'   => $monthProfit,
            'starting' => $starting,
            'ending'   => round($starting + $monthProfit, 2),
        ];
    }

    /**
     * Cash paid OUT to agents in the window — reversal-aware.
     *
     * A "debit" means the agency disbursed money to the agent (agent now owes),
     * i.e. a real payout. But because agent_transactions is append-only, we must
     * NOT count:
     *   - reversal rows themselves (reverses_id set) — a debit-typed reversal is
     *     the undo of a *credit*, not a payout; and
     *   - original debits that were later reversed (payout corrected/undone).
     *
     * So: SUM(amount) over debit rows that are neither a reversal nor reversed.
     * Genuine agent credits (repayments) are intentionally NOT netted here — per
     * the cash-basis definition they neither add revenue nor reduce cost.
     */
    public function agentPayoutCost(int $agencyId, ?string $from = null, ?string $to = null): float
    {
        // IDs of originals that have been reversed (agency-scoped first).
        $reversedIds = AgentTransaction::forAgency($agencyId)
            ->whereNotNull('reverses_id')
            ->pluck('reverses_id');

        $query = AgentTransaction::forAgency($agencyId) // tenant scope FIRST
            ->where('type', AgentTransaction::TYPE_DEBIT)
            ->whereNull('reverses_id')                  // not a reversal row
            ->whereNotIn('id', $reversedIds);           // not itself reversed

        if ($from) {
            $query->whereDate('txn_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('txn_date', '<=', $to);
        }

        return round((float) $query->sum('amount'), 2);
    }
}
