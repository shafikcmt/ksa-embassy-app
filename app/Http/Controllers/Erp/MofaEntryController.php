<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\MofaEntry;
use App\Models\Stamping;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * ERP MOFA Entry tracker (E1). Agency-scoped log with a live "Stamping বাকি"
 * counter (MOFA passports not yet stamped). No money math — payment_method is
 * a categorical tag only.
 */
class MofaEntryController extends Controller
{
    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        // Oldest-first to assign chronological Y#/M#/Total ordinals, then show
        // newest-first for the table.
        $entries = MofaEntry::forAgency($agencyId)
            ->with('createdBy:id,name')
            ->orderBy('mofa_date')->orderBy('id')
            ->get();

        $this->assignSerials($entries, 'mofa_date');
        $entries = $entries->reverse()->values();

        // "Stamping বাকি": MOFA passports with no matching stamping row.
        $stampingBaki = MofaEntry::forAgency($agencyId)
            ->whereNotIn('passport_no', Stamping::forAgency($agencyId)->select('passport_no'))
            ->count();

        return view('erp.mofa.index', [
            'entries'        => $entries,
            'stampingBaki'   => $stampingBaki,
            'paymentMethods' => MofaEntry::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        MofaEntry::create($data + [
            'agency_id'  => auth()->user()->agency_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('erp.mofa')->with('success', 'MOFA entry added.');
    }

    public function update(Request $request, MofaEntry $mofa)
    {
        $this->authorizeAgency($mofa);

        $mofa->update($this->validated($request) + ['updated_by' => auth()->id()]);

        return redirect()->route('erp.mofa')->with('success', 'MOFA entry updated.');
    }

    public function destroy(MofaEntry $mofa)
    {
        $this->authorizeAgency($mofa);

        $mofa->delete();

        return redirect()->route('erp.mofa')->with('success', 'MOFA entry deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'mofa_date'       => ['required', 'date'],
            'mofa_number'     => ['nullable', 'string', 'max:100'],
            'visa_serial'     => ['nullable', 'string', 'max:100'],
            'full_name'       => ['required', 'string', 'max:255'],
            'passport_no'     => ['required', 'string', 'max:100'],
            'reference_name'  => ['nullable', 'string', 'max:255'],
            'payment_method'  => ['nullable', Rule::in(array_keys(MofaEntry::PAYMENT_METHODS))],
            'whatsapp_number' => ['nullable', 'string', 'max:40'],
            'payment_note'    => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function authorizeAgency(MofaEntry $mofa): void
    {
        abort_unless($mofa->agency_id === auth()->user()->agency_id, 403);
    }

    /**
     * Assign display-only chronological ordinals to a date-ascending collection:
     *   t_no  running total within the agency
     *   y_no  running index within the calendar year
     *   m_no  running index within the calendar month
     * These are not stored columns — computed per request for the table.
     */
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
