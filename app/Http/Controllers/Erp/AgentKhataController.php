<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentTransaction;
use App\Services\AgentKhataService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * ERP Agent Khata (E3, sub-phase 3). Per-agent money ledger.
 *
 * Visibility follows the suite invariant: VIEWING (index/show) is open to any
 * access_erp staff; money-MOVING actions (store a debit/credit, reverse one)
 * are admin-only (abort_unless isAgencyAdmin), matching Delivery/DoubleMofa
 * payments and Expenses. All writes go through AgentKhataService; balances are
 * live-computed, never stored. Everything is scoped to the caller's agency.
 */
class AgentKhataController extends Controller
{
    public function index(AgentKhataService $khata)
    {
        $agencyId = auth()->user()->agency_id;

        $agents   = Agent::forAgency($agencyId)->orderBy('name')->get();
        $balances = $khata->balancesForAgency($agencyId); // keyed by agent_id

        $receivable = (float) $balances->filter(fn ($b) => $b > 0)->sum();
        $payable    = (float) $balances->filter(fn ($b) => $b < 0)->sum(); // negative

        return view('erp.agent-khata.index', [
            'agents'     => $agents,
            'balances'   => $balances,
            'receivable' => $receivable,
            'payable'    => abs($payable),
            'net'        => $receivable + $payable,
        ]);
    }

    /** Print the agent balances list (E7a) — reuses the EXACT index() data. */
    public function printPdf(AgentKhataService $khata, PdfGeneratorService $pdf)
    {
        $agencyId = auth()->user()->agency_id;
        $agents   = Agent::forAgency($agencyId)->orderBy('name')->get();
        $balances = $khata->balancesForAgency($agencyId); // keyed by agent_id

        $money = fn ($v) => '৳ ' . number_format((float) $v, 2);
        $receivable = (float) $balances->filter(fn ($b) => $b > 0)->sum();
        $payable    = (float) $balances->filter(fn ($b) => $b < 0)->sum(); // negative

        $columns = [
            ['label' => 'Agent'], ['label' => 'Net Balance', 'align' => 'right'], ['label' => 'Direction'],
        ];
        $rows = $agents->map(function (Agent $a) use ($balances, $money) {
            $bal = (float) ($balances[$a->id] ?? 0);
            $dir = $bal > 0 ? 'Receivable' : ($bal < 0 ? 'Payable' : 'Settled');
            return [$a->name, $money(abs($bal)), $dir];
        })->all();

        $totals = ['Totals', $money($receivable + $payable), 'Recv ' . $money($receivable) . ' · Pay ' . $money(abs($payable))];

        return $pdf->generateFromView('erp.print.list', [
            'title'    => 'Agent Khata — Balances',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $agents->count() . ' agent' . ($agents->count() === 1 ? '' : 's'),
            'columns'  => $columns,
            'rows'     => $rows,
            'totals'   => $totals,
            'empty'    => 'No agents to print.',
        ], 'agent-khata-' . now()->format('Y-m-d'));
    }

    public function show(Agent $agent, AgentKhataService $khata)
    {
        $this->authorizeAgency($agent);

        $txns = AgentTransaction::forAgency($agent->agency_id)
            ->where('agent_id', $agent->id)
            ->with('recordedBy:id,name')
            ->orderBy('txn_date')->orderBy('id')
            ->get();

        // Which originals have been reversed (to flag/lock them in the view).
        $reversedIds = $txns->whereNotNull('reverses_id')->pluck('reverses_id')->filter()->all();

        return view('erp.agent-khata.show', [
            'agent'       => $agent,
            'txns'        => $txns->reverse()->values(),
            'reversedIds' => $reversedIds,
            'balance'     => $khata->balanceFor($agent),
            'types'       => AgentTransaction::TYPES,
            'isAdmin'     => auth()->user()->isAgencyAdmin(),
        ]);
    }

    public function store(Request $request, Agent $agent, AgentKhataService $khata)
    {
        $this->authorizeAgency($agent);
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // money-moving action: admin-only

        $validated = $request->validate([
            'type'   => ['required', Rule::in(array_keys(AgentTransaction::TYPES))],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $khata->record($agent, $validated['type'], (float) $validated['amount'], $validated['note'] ?? null, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('erp.agent-khata.show', $agent)->with('success', 'Transaction recorded.');
    }

    public function reverse(Request $request, AgentTransaction $transaction, AgentKhataService $khata)
    {
        abort_unless($transaction->agency_id === auth()->user()->agency_id, 403);
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // money-moving action: admin-only

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:255'],
        ]);

        try {
            $khata->reverse($transaction, $validated['note'], auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('erp.agent-khata.show', $transaction->agent_id)->with('success', 'Transaction reversed.');
    }

    private function authorizeAgency(Agent $agent): void
    {
        abort_unless($agent->agency_id === auth()->user()->agency_id, 403);
    }
}
