<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\ManpowerCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * ERP Manpower Complete tracker (E1). Agency-scoped log; agent_id optionally
 * links to an existing Agent (feeds the E3 Agent Khata ledger later).
 *
 * The "Delivery বাকি" counter depends on the deliveries table, which does not
 * exist until E2 — so it is reported as a partial/pending placeholder here.
 * No money math in this phase.
 */
class ManpowerController extends Controller
{
    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $entries = ManpowerCompletion::forAgency($agencyId)
            ->with(['agent:id,name', 'createdBy:id,name'])
            ->orderBy('completed_date')->orderBy('id')
            ->get();

        $this->assignSerials($entries, 'completed_date');
        $entries = $entries->reverse()->values();

        // "Delivery বাকি" needs the deliveries table (E2). Until then, expose the
        // pending state to the view rather than a misleading number.
        $deliveryBakiPending = true;
        $totalManpower = $entries->count();

        $agents = Agent::forAgency($agencyId)->active()->orderBy('name')->get(['id', 'name']);

        return view('erp.manpower.index', [
            'entries'             => $entries,
            'deliveryBakiPending' => $deliveryBakiPending,
            'totalManpower'       => $totalManpower,
            'agents'              => $agents,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        ManpowerCompletion::create($data + [
            'agency_id'  => auth()->user()->agency_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('erp.manpower')->with('success', 'Manpower entry added.');
    }

    public function update(Request $request, ManpowerCompletion $manpower)
    {
        $this->authorizeAgency($manpower);

        $manpower->update($this->validated($request) + ['updated_by' => auth()->id()]);

        return redirect()->route('erp.manpower')->with('success', 'Manpower entry updated.');
    }

    public function destroy(ManpowerCompletion $manpower)
    {
        $this->authorizeAgency($manpower);

        $manpower->delete();

        return redirect()->route('erp.manpower')->with('success', 'Manpower entry deleted.');
    }

    private function validated(Request $request): array
    {
        $agencyId = auth()->user()->agency_id;

        return $request->validate([
            'completed_date' => ['required', 'date'],
            'customer_name'  => ['required', 'string', 'max:255'],
            'passport_no'    => ['required', 'string', 'max:100'],
            // agent_id must belong to the caller's own agency (or be blank).
            'agent_id'       => ['nullable', Rule::exists('agents', 'id')->where('agency_id', $agencyId)],
        ]);
    }

    private function authorizeAgency(ManpowerCompletion $manpower): void
    {
        abort_unless($manpower->agency_id === auth()->user()->agency_id, 403);
    }

    /** Display-only chronological ordinals (t_no/y_no/m_no); not stored. */
    private function assignSerials(Collection $entries, string $dateField): void
    {
        $year = [];
        $month = [];
        $total = 0;

        foreach ($entries as $e) {
            $total++;
            $y = $e->{$dateField}->format('Y');
            $m = $e->{$dateField}->format('Y-m');
            $year[$y]  = ($year[$y] ?? 0) + 1;
            $month[$m] = ($month[$m] ?? 0) + 1;

            $e->t_no = $total;
            $e->y_no = $year[$y];
            $e->m_no = $month[$m];
        }
    }
}
