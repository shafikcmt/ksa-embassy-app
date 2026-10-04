<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\MofaEntryRequest;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\HrProfile;
use App\Models\MofaEntry;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MofaController extends Controller
{
    public const COLUMNS = ['SL', 'Passenger Name', 'Father’s Name', 'Mother’s Name', 'PP No', 'D.O.B', 'Age', 'Issu Date', 'Exp. Date', 'Visa No', 'Id No', 'M-Issu.Date', 'Left Day', 'Mofa No', 'Mofa Date', 'Reference', 'Remarks'];

    public const FIELDS = ['full_name', 'father_name', 'mother_name', 'passport_number', 'date_of_birth', 'age', 'issue_date', 'expiry_date', 'visa_number', 'id_number', 'mofa_issue_date', 'left_day', 'mofa_number', 'mofa_date', 'reference', 'remarks'];

    private function query(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:active,expired,expiring,processing', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d'.($request->filled('from') ? '|after_or_equal:from' : '')]);
        $query = MofaEntry::forAgency((int) $request->user()->agency_id);
        if ($q = trim($filters['q'] ?? '')) {
            $query->where(function ($w) use ($q) {
                foreach (['full_name', 'passport_no', 'visa_serial', 'mofa_number'] as $field) {
                    $w->orWhere($field, 'like', '%'.addcslashes($q, '%_\\').'%');
                }
            });
        }
        $this->statusQuery($query, $filters['status'] ?? '');
        if ($filters['from'] ?? null) {
            $query->whereDate('mofa_date', '>=', $filters['from']);
        }
        if ($filters['to'] ?? null) {
            $query->whereDate('mofa_date', '<=', $filters['to']);
        }

        return $query->orderByDesc('id');
    }

    private function statusQuery($query, string $status): void
    {
        match ($status) {
            'processing' => $query->whereNull('mofa_expiry_date'),
            'expired' => $query->whereDate('mofa_expiry_date', '<', today()),
            'expiring' => $query->whereDate('mofa_expiry_date', '>=', today())->whereDate('mofa_expiry_date', '<', today()->addDays(30)),
            'active' => $query->whereDate('mofa_expiry_date', '>=', today()->addDays(30)),
            default => null,
        };
    }

    public function index(Request $request)
    {
        $base = MofaEntry::forAgency((int) $request->user()->agency_id);
        $stats = ['total' => $base->count()];
        foreach (['active', 'expiring', 'expired'] as $status) {
            $q = clone $base;
            $this->statusQuery($q, $status);
            $stats[$status] = $q->count();
        }

        return view('erp.mofa.index', ['entries' => $this->query($request)->paginate(20)->withQueryString(), 'stats' => $stats,
            'agentOptions' => Agent::referenceOptions((int) $request->user()->agency_id)]);
    }

    public function create()
    {
        return redirect()->route('erp.mofa', ['add' => 1]);
    }

    private function authorizeEntry(MofaEntry $mofa): void
    {
        abort_unless((int) $mofa->agency_id === (int) auth()->user()->agency_id, 403);
    }

    public function show(Request $request, MofaEntry $mofa)
    {
        $this->authorizeEntry($mofa);
        if ($request->expectsJson()) {
            $data = ['id' => $mofa->id];
            foreach (array_merge(self::FIELDS, ['mofa_expiry_date']) as $field) {
                $data[$field] = $this->value($mofa, $field, 'Y-m-d', false);
            }

            return response()->json($data);
        }

        return view('erp.mofa.show', ['entry' => $mofa]);
    }

    public function edit(MofaEntry $mofa)
    {
        $this->authorizeEntry($mofa);

        return redirect()->route('erp.mofa', ['edit' => $mofa->id]);
    }

    public function store(MofaEntryRequest $request)
    {
        return $this->save($request, new MofaEntry(['agency_id' => $request->user()->agency_id, 'created_by' => $request->user()->id]));
    }

    public function update(MofaEntryRequest $request, MofaEntry $mofa)
    {
        $this->authorizeEntry($mofa);

        return $this->save($request, $mofa);
    }

    private function save(MofaEntryRequest $request, MofaEntry $entry)
    {
        // Lock the tenant row so concurrent form saves cannot bypass uniqueness validation.
        DB::transaction(function () use ($request, $entry) {
            Agency::whereKey($request->user()->agency_id)->lockForUpdate()->firstOrFail();
            $data = $request->validated();
            $duplicate = MofaEntry::forAgency((int) $entry->agency_id)->where('passport_no', $data['passport_number'])->when($entry->exists, fn ($q) => $q->whereKeyNot($entry->id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['passport_number' => 'Passport number already exists.']);
            }
            $hr = HrProfile::forAgency((int) $entry->agency_id)->whereHas('passport', fn ($q) => $q->where('passport_number', $data['passport_number']))->first();
            $entry->fill($data)->fill(['hr_profile_id' => $hr?->id, 'updated_by' => $request->user()->id])->save();
        });
        session()->flash('success', 'MOFA entry saved successfully');

        return $request->expectsJson() ? response()->json(['id' => $entry->id, 'ok' => true]) : redirect()->route('erp.mofa');
    }

    public function destroy(MofaEntry $mofa)
    {
        $this->authorizeEntry($mofa);
        $mofa->delete();

        return redirect()->route('erp.mofa')->with('success', 'MOFA entry deleted successfully');
    }

    public function hrSearch(Request $request)
    {
        $q = $request->validate(['q' => 'required|string|min:2|max:100'])['q'];
        $profiles = HrProfile::forAgency((int) $request->user()->agency_id)->with('passport')->whereHas('passport', fn ($w) => $w->where('passport_number', 'like', addcslashes($q, '%_\\').'%'))->limit(8)->get();

        return response()->json($profiles->map(fn ($p) => ['id' => $p->id, 'full_name' => $p->full_name_en, 'father_name' => $p->father_name, 'mother_name' => $p->mother_name, 'date_of_birth' => $p->date_of_birth?->format('Y-m-d'), 'passport_number' => $p->passport->passport_number, 'issue_date' => $p->passport->issue_date?->format('Y-m-d'), 'expiry_date' => $p->passport->expiry_date?->format('Y-m-d')]));
    }

    public static function value(MofaEntry $entry, string $field, string $dateFormat = 'd-M-Y', bool $displayFallback = true)
    {
        // The edit-form JSON passes $displayFallback = false so the MOFA Date
        // fallback (see MofaEntry::displayMofaIssueDate) is never saved back.
        $value = $displayFallback && $field === 'mofa_issue_date' ? $entry->displayMofaIssueDate() : $entry->$field;

        return $value instanceof \DateTimeInterface ? $value->format($dateFormat) : $value;
    }

    public function exportCsv(Request $request)
    {
        $entries = $this->query($request)->get();

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::COLUMNS);
            foreach ($entries as $i => $entry) {
                $row = [$i + 1];
                foreach (self::FIELDS as $field) {
                    $v = (string) self::value($entry, $field);
                    $row[] = preg_match('/^[\s]*[=+@-]/u', $v) ? "'".$v : $v;
                }
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'mofa-summary.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function printPdf(Request $request, PdfGeneratorService $pdf, ?MofaEntry $mofa = null)
    {
        if ($mofa) {
            $this->authorizeEntry($mofa);
        }
        $options = $request->validate(['layout' => 'nullable|in:landscape,portrait,auto', 'page' => 'nullable|integer|min:1', 'current_page' => 'nullable|boolean']);
        $layout = $options['layout'] ?? 'landscape';
        $entries = $mofa ? collect([$mofa]) : ($request->boolean('current_page') ? $this->query($request)->paginate(20)->getCollection() : $this->query($request)->get());
        if ($layout === 'auto') {
            $layout = $entries->count() === 1 ? 'portrait' : 'landscape';
        }
        $data = ['entries' => $entries, 'agency' => $request->user()->agency, 'generated' => now(), 'layout' => $layout];
        if ($request->boolean('preview')) {
            return view('prints.mofa-summary', $data);
        }

        return $pdf->generateFromView('prints.mofa-summary', $data, 'mofa-summary', true, \App\Support\ErpPrintTheme::mpdfOptions($layout === 'portrait' ? 'portrait' : 'landscape'));
    }
}
