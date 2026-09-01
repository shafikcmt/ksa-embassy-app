<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Delivery;
use App\Models\ManpowerCompletion;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * ERP Manpower Complete tracker (E1). Agency-scoped log; agent_id optionally
 * links to an existing Agent (feeds the E3 Agent Khata ledger later).
 *
 * The "Delivery বাকি" counter (manpower passports not yet delivered) went live
 * with E2 once the deliveries table existed. No money math happens here.
 */
class ManpowerController extends Controller
{
    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $entries = $this->listing($agencyId);

        // "Delivery বাকি": manpower-complete passports with no matching delivery row.
        $deliveryBaki = ManpowerCompletion::forAgency($agencyId)
            ->whereNotIn('passport_no', Delivery::forAgency($agencyId)->select('passport_no'))
            ->count();
        $totalManpower = $entries->count();

        $agents = Agent::forAgency($agencyId)->active()->orderBy('name')->get(['id', 'name']);

        return view('erp.manpower.index', [
            'entries'       => $entries,
            'deliveryBaki'  => $deliveryBaki,
            'totalManpower' => $totalManpower,
            'agents'        => $agents,
        ]);
    }

    /** Print the full module list (E7a) — reuses the EXACT index() query. */
    public function printPdf(PdfGeneratorService $pdf)
    {
        $entries = $this->listing(auth()->user()->agency_id);

        $columns = [
            ['label' => '#', 'align' => 'right'], ['label' => 'Date'],
            ['label' => 'Customer'], ['label' => 'Passport'], ['label' => 'Agent'],
        ];
        $rows = $entries->map(fn (ManpowerCompletion $e) => [
            $e->t_no, $e->completed_date->format('d M Y'),
            $e->customer_name, $e->passport_no, $e->agent->name ?? '—',
        ])->all();

        return $pdf->generateFromView('erp.print.list', [
            'title'    => 'Manpower Complete',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $entries->count() . ' entr' . ($entries->count() === 1 ? 'y' : 'ies'),
            'columns'  => $columns,
            'rows'     => $rows,
        ], 'manpower-' . now()->format('Y-m-d'));
    }

    /** Shared listing used by both index() and printPdf() (serials + newest-first). */
    private function listing(int $agencyId): Collection
    {
        $entries = ManpowerCompletion::forAgency($agencyId)
            ->with(['agent:id,name', 'createdBy:id,name'])
            ->orderBy('completed_date')->orderBy('id')
            ->get();

        $this->assignSerials($entries, 'completed_date');

        return $entries->reverse()->values();
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
