<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\VisaStampingRequest;
use App\Models\Agent;
use App\Models\HrProfile;
use App\Models\MofaEntry;
use App\Models\Stamping;
use App\Models\VisaStamping;
use App\Services\PdfGeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Visa Stamping — modal-based management over the existing `stampings`
 * log (VisaStamping model). Agency-scoped; workflow status only, no money.
 *
 * List (search / status / agent / date-range filters, sort, 20 per page, stats)
 * with an Add/Edit modal that saves over JSON; passport → HR-profile dropdown
 * and MOFA auto-fill; per-entry and full-list "Visa Stamping Summary" print
 * (A4 landscape); CSV export; soft delete + admin restore.
 *
 * The older StampingController keeps serving the legacy CSV import and its
 * update/delete endpoints; /erp/stamping now redirects here.
 */
class VisaStampingController extends Controller
{
    private const SORTS = [
        'stamping_date' => 'Stamping date',
        'expiry_date'   => 'Expiry date',
        'name'          => 'Name',
        'status'        => 'Status',
    ];

    private const DATE_FIELDS = ['stamping' => 'Stamping date', 'expiry' => 'Expiry date'];

    private const CSV_HEADERS = [
        'full_name', 'father_name', 'mother_name', 'passport_number', 'date_of_birth', 'age',
        'visa_number', 'id_number', 'mofa_number', 'mofa_date', 'issued_visa_number',
        'issued_date', 'expiry_date', 'left_day', 'stamping_date', 'status', 'agent', 'reference', 'remarks',
    ];

    public function index(Request $request)
    {
        $agencyId = (int) auth()->user()->agency_id;
        $filters  = $this->filters($request);

        $counts = VisaStamping::forAgency($agencyId)
            ->selectRaw('status, COUNT(*) AS c')->groupBy('status')->pluck('c', 'status');

        return view('erp.visa-stamping.index', [
            'entries'    => $this->filteredQuery($agencyId, $filters)->paginate(20)->withQueryString(),
            'filters'    => $filters,
            'statuses'   => Stamping::STATUSES,
            'sorts'      => self::SORTS,
            'dateFields' => self::DATE_FIELDS,
            'agents'     => $this->agents($agencyId),
            'stats'      => [
                'total'   => (int) $counts->sum(),
                'stamped' => (int) ($counts['stamped'] ?? 0),
                'pending' => (int) ($counts['pending'] ?? 0),
                'expired' => (int) ($counts['expired'] ?? 0),
            ],
        ]);
    }

    /** The Add form is a modal on the list page — this deep link opens it. */
    public function create()
    {
        return redirect()->route('erp.visa-stamping.index', ['add' => 1]);
    }

    public function store(VisaStampingRequest $request)
    {
        $agencyId = (int) auth()->user()->agency_id;
        $data     = $request->validated();

        $entry = VisaStamping::create($data + [
            'agency_id'     => $agencyId,
            'hr_profile_id' => $this->hrProfileIdFor($agencyId, $data['passport_number']),
            'created_by'    => auth()->id(),
            'updated_by'    => auth()->id(),
        ]);

        return $this->saved($request, $entry, 'Visa stamping entry saved successfully');
    }

    /** Detail page; JSON for the Edit modal when the client asks for it. */
    public function show(Request $request, VisaStamping $visaStamping)
    {
        $this->authorizeAgency($visaStamping);

        if ($request->expectsJson()) {
            return response()->json($this->formData($visaStamping));
        }

        $visaStamping->load(['createdBy:id,name', 'updatedBy:id,name', 'agent:id,name', 'hrProfile:id,full_name_en,file_number']);

        return view('erp.visa-stamping.show', ['entry' => $visaStamping]);
    }

    /** The Edit form is a modal on the list page — this deep link opens it. */
    public function edit(VisaStamping $visaStamping)
    {
        $this->authorizeAgency($visaStamping);

        return redirect()->route('erp.visa-stamping.index', ['edit' => $visaStamping->id]);
    }

    public function update(VisaStampingRequest $request, VisaStamping $visaStamping)
    {
        $this->authorizeAgency($visaStamping);
        $data = $request->validated();

        $visaStamping->update($data + [
            'hr_profile_id' => $this->hrProfileIdFor($visaStamping->agency_id, $data['passport_number']),
            'updated_by'    => auth()->id(),
        ]);

        return $this->saved($request, $visaStamping, 'Visa stamping entry updated successfully');
    }

    /** Soft delete — kept with deleted_at; an admin can restore it. */
    public function destroy(VisaStamping $visaStamping)
    {
        $this->authorizeAgency($visaStamping);

        $visaStamping->delete();

        return redirect()->route('erp.visa-stamping.index')->with('stamping_toast', 'Entry deleted successfully');
    }

    public function restore(int $id)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $agencyId = (int) auth()->user()->agency_id;
        $entry    = VisaStamping::onlyTrashed()->forAgency($agencyId)->findOrFail($id);

        // Restoring must not create a second live entry for the same passport.
        if (VisaStamping::forAgency($agencyId)->where('passport_no', $entry->passport_no)->exists()) {
            return redirect()->route('erp.visa-stamping.index', ['trashed' => 1])
                ->with('stamping_toast_error', "Passport {$entry->passport_no} already has an active entry — restore skipped.");
        }

        $entry->restore();

        return redirect()->route('erp.visa-stamping.index')
            ->with('stamping_toast', 'Entry restored successfully')
            ->with('stamping_highlight', $entry->id);
    }

    /** Passport dropdown: up to 8 agency HR profiles by passport prefix or name. */
    public function hrSearch(Request $request): JsonResponse
    {
        $q    = trim((string) $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q']);
        $like = $this->escapeLike($q);

        $profiles = HrProfile::forAgency((int) auth()->user()->agency_id)
            ->with('passport:id,hr_profile_id,passport_number')
            ->where(function (Builder $w) use ($like) {
                $w->whereHas('passport', fn ($p) => $p->where('passport_number', 'like', $like . '%'))
                  ->orWhere('full_name_en', 'like', '%' . $like . '%');
            })
            ->latest('updated_at')->limit(8)->get();

        return response()->json($profiles->map(fn (HrProfile $hr) => [
            'id'              => $hr->id,
            'file_number'     => $hr->file_number,
            'passport_number' => $hr->passport?->passport_number,
            'full_name'       => $hr->full_name_en,
            'father_name'     => $hr->father_name,
            'mother_name'     => $hr->mother_name,
            'date_of_birth'   => $hr->date_of_birth?->format('Y-m-d'),
        ])->values());
    }

    /** Latest MOFA entry for a passport (agency-scoped) — fills the MOFA / visa fields. */
    public function mofaLookup(Request $request): JsonResponse
    {
        $passport = strtoupper(trim((string) $request->validate(['passport' => ['required', 'string', 'max:100']])['passport']));

        $mofa = MofaEntry::forAgency((int) auth()->user()->agency_id)
            ->where('passport_no', $passport)
            ->latest('updated_at')->first();

        if (! $mofa) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'         => true,
            'id'            => $mofa->id,
            'mofa_number'   => $mofa->mofa_number,
            'mofa_date'     => $mofa->mofa_date?->format('Y-m-d'),
            'visa_number'   => $mofa->visa_serial,
            'id_number'     => $mofa->id_number,
            // MOFA's issue/expiry are PASSPORT dates — never the visa issue/expiry.
            'passport_issue_date'  => $mofa->issue_date?->format('Y-m-d'),
            'passport_expiry_date' => $mofa->expiry_date?->format('Y-m-d'),
            'full_name'     => $mofa->full_name,
            'father_name'   => $mofa->father_name,
            'mother_name'   => $mofa->mother_name,
            'date_of_birth' => $mofa->date_of_birth?->format('Y-m-d'),
        ]);
    }

    /** Visa Stamping Summary for one entry, or the whole filtered list. */
    public function printPdf(Request $request, PdfGeneratorService $pdf, ?VisaStamping $visaStamping = null): Response
    {
        $agencyId = (int) auth()->user()->agency_id;

        if ($visaStamping) {
            $this->authorizeAgency($visaStamping);
            $entries  = collect([$visaStamping->loadMissing('agent:id,name')]);
            $filename = 'visa-stamping-' . $visaStamping->passport_no;
            $backUrl  = route('erp.visa-stamping.show', $visaStamping);
        } else {
            $entries  = $this->filteredQuery($agencyId, $this->filters($request))->get();
            $filename = 'visa-stamping-summary-' . now()->format('Y-m-d');
            $backUrl  = route('erp.visa-stamping.index', $request->query());
        }

        $data = ['agency' => auth()->user()->agency, 'entries' => $entries, 'generated' => now()];

        if ($request->boolean('download')) {
            return $pdf->generateFromView('prints.visa-stamping-summary', $data, $filename, false, \App\Support\ErpPrintTheme::mpdfOptions());
        }

        return response()->view('prints.visa-stamping-summary', $data + [
            '_downloadUrl' => $request->fullUrlWithQuery(['download' => 1]),
            '_backUrl'     => $backUrl,
        ]);
    }

    /** CSV export of the filtered list (staff-visible, like Print). */
    public function exportCsv(Request $request): StreamedResponse
    {
        $entries = $this->filteredQuery((int) auth()->user()->agency_id, $this->filters($request))->get();
        $ymd = fn ($d) => $d?->format('Y-m-d');

        return response()->streamDownload(function () use ($entries, $ymd) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            foreach ($entries as $e) {
                fputcsv($out, [
                    $e->full_name, $e->father_name, $e->mother_name, $e->passport_no,
                    $ymd($e->date_of_birth), $e->age, $e->visa_number, $e->id_number,
                    $e->mofa_number, $ymd($e->mofa_date), $e->issued_visa_number,
                    $ymd($e->issued_date), $ymd($e->expiry_date), $e->left_day,
                    $ymd($e->stamp_date), $e->statusLabel(), $e->agent?->name, $e->reference, $e->remarks,
                ]);
            }
            fclose($out);
        }, 'visa-stamping-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function saved(Request $request, VisaStamping $entry, string $message)
    {
        session()->flash('stamping_toast', $message);
        session()->flash('stamping_highlight', $entry->id);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'id' => $entry->id, 'message' => $message])
            : redirect()->route('erp.visa-stamping.index');
    }

    private function filters(Request $request): array
    {
        $status    = (string) $request->query('status', '');
        $sort      = (string) $request->query('sort', 'stamping_date');
        $dateField = (string) $request->query('date_field', 'stamping');
        $agentId   = (int) $request->query('agent_id', 0);
        $date = function (string $key) use ($request): string {
            $v = (string) $request->query($key, '');
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : '';
        };

        return [
            'q'          => trim((string) $request->query('q', '')),
            'status'     => array_key_exists($status, Stamping::STATUSES) ? $status : '',
            'agent_id'   => $agentId > 0 ? $agentId : null,
            'date_field' => array_key_exists($dateField, self::DATE_FIELDS) ? $dateField : 'stamping',
            'from'       => $date('from'),
            'to'         => $date('to'),
            'sort'       => array_key_exists($sort, self::SORTS) ? $sort : 'stamping_date',
            'dir'        => $request->query('dir') === 'asc' ? 'asc' : 'desc',
            'trashed'    => $request->boolean('trashed') && auth()->user()->isAgencyAdmin(),
        ];
    }

    /** One query for the list, print and CSV — always agency-scoped. */
    private function filteredQuery(int $agencyId, array $f): Builder
    {
        $query = VisaStamping::forAgency($agencyId)->with('agent:id,name');

        if ($f['trashed']) {
            $query->onlyTrashed();
        }

        if ($f['q'] !== '') {
            $like = '%' . $this->escapeLike($f['q']) . '%';
            $query->where(function (Builder $w) use ($like) {
                foreach (['full_name', 'passport_no', 'visa_number', 'mofa_number', 'reference'] as $col) {
                    $w->orWhere($col, 'like', $like);
                }
            });
        }

        if ($f['status'] !== '') {
            $query->where('status', $f['status']);
        }
        if ($f['agent_id']) {
            $query->where('agent_id', $f['agent_id']);
        }

        $dateCol = $f['date_field'] === 'expiry' ? 'expiry_date' : 'stamp_date';
        if ($f['from'] !== '') {
            $query->whereDate($dateCol, '>=', $f['from']);
        }
        if ($f['to'] !== '') {
            $query->whereDate($dateCol, '<=', $f['to']);
        }

        $dir = $f['dir'];
        match ($f['sort']) {
            'expiry_date' => $query->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date', $dir),
            'name'        => $query->orderBy('full_name', $dir),
            'status'      => $query->orderBy('status', $dir),
            default       => $query->orderBy('stamp_date', $dir),
        };

        return $query->orderBy('id', $dir);
    }

    private function agents(int $agencyId): Collection
    {
        return Agent::forAgency($agencyId)->active()->orderBy('name')->get(['id', 'name']);
    }

    private function hrProfileIdFor(int $agencyId, string $passport): ?int
    {
        return HrProfile::forAgency($agencyId)
            ->whereHas('passport', fn ($q) => $q->where('passport_number', $passport))
            ->latest('updated_at')->value('id');
    }

    /** Field values the modal is filled with (dates as Y-m-d). */
    private function formData(VisaStamping $e): array
    {
        $ymd = fn ($d) => $d?->format('Y-m-d');

        return [
            'id'                 => $e->id,
            'full_name'          => $e->full_name,
            'passport_number'    => $e->passport_no,
            'visa_number'        => $e->visa_number,
            'id_number'          => $e->id_number,
            'stamping_date'      => $ymd($e->stamp_date),
            'status'             => $e->status,
            'agent_id'           => $e->agent_id,
            'reference'          => $e->reference,
            'father_name'        => $e->father_name,
            'mother_name'        => $e->mother_name,
            'date_of_birth'      => $ymd($e->date_of_birth),
            'mofa_number'        => $e->mofa_number,
            'mofa_date'          => $ymd($e->mofa_date),
            'issued_visa_number' => $e->issued_visa_number,
            'issued_date'        => $ymd($e->issued_date),
            'expiry_date'        => $ymd($e->expiry_date),
            'remarks'            => $e->remarks,
        ];
    }

    private function escapeLike(string $v): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $v);
    }

    private function authorizeAgency(VisaStamping $entry): void
    {
        abort_unless((int) $entry->agency_id === (int) auth()->user()->agency_id, 403);
    }
}
