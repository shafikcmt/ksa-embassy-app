<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\MofaEntryRequest;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\HrProfile;
use App\Models\MofaEntry;
use App\Services\MofaSyncService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MofaController extends Controller
{
    public const COLUMNS = ['SL', 'Passenger Name', 'Father’s Name', 'Mother’s Name', 'PP No', 'D.O.B', 'Age', 'Issu Date', 'Exp. Date', 'Visa No', 'Id No', 'M-Issu.Date', 'Left Day', 'Mofa No', 'Mofa Date', 'Reference', 'Remarks'];

    public const FIELDS = ['full_name', 'father_name', 'mother_name', 'passport_number', 'date_of_birth', 'age', 'issue_date', 'expiry_date', 'visa_number', 'id_number', 'mofa_issue_date', 'left_day', 'mofa_number', 'mofa_date', 'reference', 'remarks'];

    /** Summary PDF rows per table / mPDF WriteHTML() call; keeps each call far below the 1 MB PCRE limit. */
    public const PDF_CHUNK_ROWS = 150;

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
        $sync = app(MofaSyncService::class);
        // Lock the tenant row so concurrent saves for this agency stay serialised.
        // A passport may have several MOFA entries, so there is no duplicate check.
        DB::transaction(function () use ($request, $entry, $sync) {
            Agency::whereKey($request->user()->agency_id)->lockForUpdate()->firstOrFail();
            $data = $request->validated();
            $hr = HrProfile::forAgency((int) $entry->agency_id)->whereHas('passport', fn ($q) => $q->where('passport_number', $data['passport_number']))->first();
            $isNew = ! $entry->exists;
            $entry->fill($data)->fill(['hr_profile_id' => $hr?->id, 'updated_by' => $request->user()->id])->save();
            // Push the candidate data to Double MOFA / Stamping / BMET / Delivery.
            $isNew ? $sync->created($entry) : $sync->updated($entry);
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

    /**
     * Non-blocking form hint: how many MOFA entries this agency already has for a
     * passport (optionally excluding the entry being edited). Never another agency's.
     */
    public function passportCount(Request $request)
    {
        $agencyId = (int) $request->user()->agency_id;
        abort_if($agencyId === 0, 403);
        $data = $request->validate(['passport' => 'required|string|max:100', 'exclude' => 'nullable|integer']);
        $count = MofaEntry::forAgency($agencyId)
            ->where('passport_no', strtoupper(trim($data['passport'])))
            ->when($data['exclude'] ?? null, fn ($q, $id) => $q->whereKeyNot($id))
            ->count();

        return response()->json(['count' => $count]);
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
        // Accept old layout links, but always render the single landscape report.
        $request->validate(['layout' => 'nullable|in:landscape,portrait,auto', 'page' => 'nullable|integer|min:1', 'current_page' => 'nullable|boolean']);
        $entries = $mofa ? collect([$mofa]) : ($request->boolean('current_page') ? $this->query($request)->paginate(20)->getCollection() : $this->query($request)->get());
        $data = ['entries' => $entries, 'agency' => $request->user()->agency, 'generated' => now()];
        // Default → browser print preview (opens the print dialog); ?download=1 → mPDF file.
        if (! $request->boolean('download')) {
            return view('prints.mofa-summary', $data + [
                '_downloadUrl' => $request->fullUrlWithQuery(['download' => 1, 'preview' => null]),
                '_backUrl'     => $mofa ? route('erp.mofa.show', $mofa) : route('erp.mofa', $request->only('q', 'status', 'from', 'to')),
            ]);
        }

        // Rows go to mPDF in chunks: one huge table breaks pcre.backtrack_limit (~220 rows) and memory.
        return $pdf->generateChunkedFromView('prints.mofa-summary', $data, '<!--mofa-rows-->', self::pdfRowChunks($entries),
            'mofa-summary', false, \App\Support\ErpPrintTheme::mpdfOptions('landscape'));
    }

    /** Lazily renders the summary rows as complete tables of PDF_CHUNK_ROWS rows (SL and striping continue). */
    public static function pdfRowChunks(\Illuminate\Support\Collection $entries): \Generator
    {
        $total = $entries->count();
        $chunks = $total ? $entries->values()->chunk(self::PDF_CHUNK_ROWS) : collect([collect()]);
        foreach ($chunks as $i => $rows) {
            yield view('prints.partials.mofa-summary-table', ['rows' => $rows, 'offset' => $i * self::PDF_CHUNK_ROWS,
                'total' => $total, 'last' => $i === $chunks->count() - 1, '_pdf' => true])->render();
        }
    }
}
