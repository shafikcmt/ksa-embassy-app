<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * AgentKhataService — the ONLY code that writes agent ledger rows.
 *
 * Separate from ErpPaymentService (different domain: per-agent debit/credit
 * ledger + opening balance, not payable receipts) but shares the SAME integrity
 * safeguards:
 *  - agent_transactions is append-only (never updated/deleted); a mistake is
 *    corrected with a reversal row.
 *  - reversal requires a note; reverses_id is UNIQUE so a row is reversible at
 *    most once (guarded here + DB-enforced).
 *  - reversal runs in a DB transaction with lockForUpdate on the original row so
 *    two concurrent reversals serialize instead of both booking.
 *  - the agent balance is LIVE-COMPUTED (opening_balance + signed-sum) — never
 *    stored, so there is no cache to drift and nothing to mass-assign.
 */
class AgentKhataService
{
    /**
     * Append a debit/credit row to an agent's khata.
     *
     * @throws RuntimeException on non-positive amount or bad type.
     */
    public function record(Agent $agent, string $type, float $amount, ?string $note, int $userId): AgentTransaction
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than zero.');
        }
        if (! in_array($type, [AgentTransaction::TYPE_DEBIT, AgentTransaction::TYPE_CREDIT], true)) {
            throw new RuntimeException('Invalid transaction type.');
        }

        return DB::transaction(fn () => AgentTransaction::create([
            'agency_id'   => $agent->agency_id,
            'agent_id'    => $agent->id,
            'txn_date'    => now()->toDateString(),
            'type'        => $type,
            'amount'      => $amount,
            'note'        => $note !== null && trim($note) !== '' ? trim($note) : null,
            'recorded_by' => $userId,
        ]));
    }

    /**
     * Reverse a prior (non-reversal) transaction. Books a row of the OPPOSITE
     * type and equal amount, pointing at the original via reverses_id (UNIQUE),
     * so the signed-sum cancels. A note (reason) is required.
     *
     * @throws RuntimeException if the target is itself a reversal, already
     *                          reversed, or no note is given.
     */
    public function reverse(AgentTransaction $txn, string $note, int $userId): AgentTransaction
    {
        $note = trim($note);
        if ($note === '') {
            throw new RuntimeException('A reason (note) is required to reverse a transaction.');
        }
        if ($txn->isReversal()) {
            throw new RuntimeException('A reversal cannot itself be reversed.');
        }

        return DB::transaction(function () use ($txn, $note, $userId) {
            // Re-read the original under lock so concurrent reversals serialize.
            $original = AgentTransaction::whereKey($txn->getKey())->lockForUpdate()->firstOrFail();

            if ($original->isReversal()) {
                throw new RuntimeException('A reversal cannot itself be reversed.');
            }
            if (AgentTransaction::where('reverses_id', $original->id)->lockForUpdate()->exists()) {
                throw new RuntimeException('This transaction has already been reversed.');
            }

            $opposite = $original->type === AgentTransaction::TYPE_DEBIT
                ? AgentTransaction::TYPE_CREDIT
                : AgentTransaction::TYPE_DEBIT;

            return AgentTransaction::create([
                'agency_id'   => $original->agency_id,
                'agent_id'    => $original->agent_id,
                'txn_date'    => now()->toDateString(),
                'type'        => $opposite,
                'amount'      => $original->amount,
                'note'        => $note,
                'reverses_id' => $original->id,
                'recorded_by' => $userId,
            ]);
        });
    }

    /** Live balance for one agent: opening_balance + signed-sum(ledger). */
    public function balanceFor(Agent $agent): float
    {
        $net = (float) AgentTransaction::forAgency($agent->agency_id)
            ->where('agent_id', $agent->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN type='credit' THEN -amount ELSE amount END),0) net")
            ->value('net');

        return round((float) $agent->opening_balance + $net, 2);
    }

    /**
     * Live balance for every agent in an agency, keyed by agent_id — one grouped
     * query for the ledger totals, merged with each agent's opening balance.
     */
    public function balancesForAgency(int $agencyId): Collection
    {
        $ledger = AgentTransaction::forAgency($agencyId)
            ->selectRaw("agent_id, COALESCE(SUM(CASE WHEN type='credit' THEN -amount ELSE amount END),0) net")
            ->groupBy('agent_id')
            ->pluck('net', 'agent_id');

        return Agent::forAgency($agencyId)->get(['id', 'opening_balance'])
            ->mapWithKeys(fn (Agent $a) => [
                $a->id => round((float) $a->opening_balance + (float) ($ledger[$a->id] ?? 0), 2),
            ]);
    }
}
