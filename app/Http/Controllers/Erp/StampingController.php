<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\ManpowerCompletion;
use App\Models\Stamping;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * ERP Stamping tracker (E1). Agency-scoped log with a live "Manpower বাকি"
 * counter (stamped passports not yet completed as manpower). No money math.
 */
class StampingController extends Controller
{
    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $entries = $this->listing($agencyId);

        // "Manpower বাকি": stamped passports with no matching manpower row.
        $manpowerBaki = Stamping::forAgency($agencyId)
            ->whereNotIn('passport_no', ManpowerCompletion::forAgency($agencyId)->select('passport_no'))
            ->count();

        return view('erp.stamping.index', [
            'entries'      => $entries,
            'manpowerBaki' => $manpowerBaki,
            'statuses'     => Stamping::STATUSES,
        ]);
    }

    /** Print the full module list (E7a) — reuses the EXACT index() query. */
    public function printPdf(PdfGeneratorService $pdf)
    {
        $entries = $this->listing(auth()->user()->agency_id);

        $columns = [
            ['label' => 'Y#', 'align' => 'right'], ['label' => 'M#', 'align' => 'right'],
            ['label' => 'Date'], ['label' => 'Visa Serial'], ['label' => 'Name'], ['label' => 'Passport'],
            ['label' => 'Visa No'], ['label' => 'ID'], ['label' => 'Reference'], ['label' => 'Status'],
        ];
        $rows = $entries->map(fn (Stamping $e) => [
            $e->y_no, $e->m_no, $e->stamp_date->format('d M Y'),
            $e->visa_serial ?: '—', $e->full_name, $e->passport_no,
            $e->visa_number ?: '—', $e->id_number ?: '—', $e->reference ?: '—', $e->statusLabel(),
        ])->all();

        return $pdf->generateFromView('erp.print.list', [
            'title'    => 'Stamping',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $entries->count() . ' entr' . ($entries->count() === 1 ? 'y' : 'ies'),
            'columns'  => $columns,
            'rows'     => $rows,
        ], 'stamping-' . now()->format('Y-m-d'));
    }

    /** Shared listing used by both index() and printPdf() (serials + newest-first). */
    private function listing(int $agencyId): Collection
    {
        $entries = Stamping::forAgency($agencyId)
            ->with('createdBy:id,name')
            ->orderBy('stamp_date')->orderBy('id')
            ->get();

        $this->assignSerials($entries, 'stamp_date');

        return $entries->reverse()->values();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Stamping::create($data + [
            'agency_id'  => auth()->user()->agency_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('erp.stamping')->with('success', 'Stamping entry added.');
    }

    public function update(Request $request, Stamping $stamping)
    {
        $this->authorizeAgency($stamping);

        $stamping->update($this->validated($request) + ['updated_by' => auth()->id()]);

        return redirect()->route('erp.stamping')->with('success', 'Stamping entry updated.');
    }

    public function destroy(Stamping $stamping)
    {
        $this->authorizeAgency($stamping);

        $stamping->delete();

        return redirect()->route('erp.stamping')->with('success', 'Stamping entry deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'stamp_date'  => ['required', 'date'],
            'visa_serial' => ['nullable', 'string', 'max:100'],
            'full_name'   => ['required', 'string', 'max:255'],
            'passport_no' => ['required', 'string', 'max:100'],
            'visa_number' => ['nullable', 'string', 'max:100'],
            'id_number'   => ['nullable', 'string', 'max:100'],
            'reference'   => ['nullable', 'string', 'max:255'],
            'status'      => ['required', Rule::in(array_keys(Stamping::STATUSES))],
        ]);
    }

    private function authorizeAgency(Stamping $stamping): void
    {
        abort_unless($stamping->agency_id === auth()->user()->agency_id, 403);
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
