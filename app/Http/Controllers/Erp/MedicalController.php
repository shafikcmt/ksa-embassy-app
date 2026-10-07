<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicalEntryRequest;
use App\Models\Agent;
use App\Models\HrProfile;
use App\Models\Medical;
use App\Services\CsvImportService;
use App\Services\PdfGeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Medical Entry (E1 tracker, extended into the full Medical Summary).
 * Agency-scoped medical-check log. Workflow status only — no money math.
 *
 * List + search/filter/sort/paginate, dedicated Add / View / Edit pages, a
 * per-entry and a full-list "Medical Summary" print (reference column order,
 * A4 landscape), soft delete + admin restore, and the CSV export (staff) /
 * import (admin, dry-run preview + all-or-nothing commit) via CsvImportService.
 * Validation lives in MedicalEntryRequest and is shared with the import.
 */
class MedicalController extends Controller
{
    /** CSV columns in Medical Summary reference order (age is derived, never imported). */
    private const CSV_HEADERS = [
        'full_name', 'father_name', 'passport_no', 'date_of_birth',
        'medical_center_name', 'country', 'medical_code',
        'medical_issue_date', 'medical_expire_date', 'medical_status',
        'mobile_no', 'reference', 'remarks',
    ];

    private const OPTIONAL_CSV_FIELDS = ['medical_code', 'mobile_no', 'reference', 'remarks'];

    private const IMPORT_SESSION_KEY = 'erp_medical_import_path';

    /** Suggestions for the modal's Country combobox (free text is still allowed). */
    private const COUNTRIES = ['Saudi Arabia', 'UAE', 'Kuwait', 'Qatar', 'Bahrain', 'Oman'];

    private const SORTS = [
        'issue_asc'   => 'Issue date (oldest first)',
        'issue_desc'  => 'Issue date (newest first)',
        'expiry_asc'  => 'Expiry date (soonest first)',
        'name_asc'    => 'Name (A–Z)',
        'status'      => 'Status',
        'latest'      => 'Recently added',
    ];

    public function index(Request $request)
    {
        $agencyId = auth()->user()->agency_id;
        $filters  = $this->filters($request);

        $counts = Medical::forAgency($agencyId)
            ->selectRaw('medical_status, COUNT(*) AS c')
            ->groupBy('medical_status')
            ->pluck('c', 'medical_status');

        return view('erp.medical.index', [
            'entries'   => $this->filteredQuery($agencyId, $filters)->paginate(20)->withQueryString(),
            'filters'   => $filters,
            'statuses'  => Medical::MEDICAL_STATUSES,
            'sorts'     => self::SORTS,
            'countries' => self::COUNTRIES,
            'agentOptions' => Agent::referenceOptions($agencyId),
            'stats'     => [
                'total'   => (int) $counts->sum(),
                'pending' => (int) ($counts['pending'] ?? 0),
                'fit'     => (int) ($counts['fit'] ?? 0),
                'expired' => (int) ($counts['expired'] ?? 0),
            ],
        ]);
    }

    /** The Add form lives in a modal on the list page — /medical/add opens it. */
    public function create()
    {
        return redirect()->route('erp.medical', ['add' => 1]);
    }

    public function store(MedicalEntryRequest $request)
    {
        $data     = $request->validated();
        $agencyId = auth()->user()->agency_id;

        // Warn-and-confirm on a duplicate passport (medical renewals are legit).
        // Same forAgency + passport_no predicate as the CSV-import notice; the
        // latest expiry is shown so staff see the most recent medical.
        if (! $request->boolean('confirm_duplicate')) {
            $existing = Medical::forAgency($agencyId)
                ->where('passport_no', $data['passport_no'])
                ->orderByDesc('medical_expire_date')
                ->first();

            if ($existing) {
                $issue   = $existing->medical_issue_date?->format('d M Y') ?? 'not set';
                $expire  = $existing->medical_expire_date?->format('d M Y') ?? 'not set';
                $message = "A Medical entry already exists for this passport (issued {$issue}, expires {$expire}).";

                if ($request->expectsJson()) {
                    return response()->json(['duplicate' => true, 'message' => $message], 409);
                }

                return back()->withInput()->with('duplicate_warning', $message);
            }
        }

        $medical = Medical::create($data + [
            'agency_id'     => $agencyId,
            'hr_profile_id' => $this->hrProfileIdFor($agencyId, $data['passport_no']),
            'created_by'    => auth()->id(),
            'updated_by'    => auth()->id(),
        ]);

        return $this->saved($request, $medical, 'Medical entry saved successfully.');
    }

    /** Detail page; JSON (for the Edit modal) when the client asks for it. */
    public function show(Request $request, Medical $medical)
    {
        $this->authorizeAgency($medical);

        if ($request->expectsJson()) {
            return response()->json($this->formData($medical));
        }

        $medical->load(['createdBy:id,name', 'updatedBy:id,name', 'hrProfile:id,full_name_en,file_number']);

        return view('erp.medical.show', ['entry' => $medical]);
    }

    /** The Edit form lives in a modal on the list page — this deep link opens it. */
    public function edit(Medical $medical)
    {
        $this->authorizeAgency($medical);

        return redirect()->route('erp.medical', ['edit' => $medical->id]);
    }

    public function update(MedicalEntryRequest $request, Medical $medical)
    {
        $this->authorizeAgency($medical);
        $data = $request->validated();

        $medical->update($data + [
            'hr_profile_id' => $this->hrProfileIdFor($medical->agency_id, $data['passport_no']),
            'updated_by'    => auth()->id(),
        ]);

        return $this->saved($request, $medical, 'Medical entry updated successfully.');
    }

    /**
     * Modal saves arrive as JSON: flash the toast + row highlight for the list
     * reload the modal triggers, and answer with JSON. Plain form posts redirect.
     */
    private function saved(Request $request, Medical $medical, string $message)
    {
        if ($request->expectsJson()) {
            session()->flash('medical_toast', $message);
            session()->flash('medical_highlight', $medical->id);

            return response()->json(['ok' => true, 'id' => $medical->id, 'message' => $message]);
        }

        return redirect()->route('erp.medical.show', $medical)->with('success', $message);
    }

    /** Soft delete — the row stays in the table with deleted_at and can be restored. */
    public function destroy(Medical $medical)
    {
        $this->authorizeAgency($medical);

        $medical->delete();

        return redirect()->route('erp.medical')->with('medical_toast', 'Medical entry deleted. An admin can restore it from “Deleted entries”.');
    }

    public function restore(int $id)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $medical = Medical::onlyTrashed()->forAgency(auth()->user()->agency_id)->findOrFail($id);
        $medical->restore();

        return redirect()->route('erp.medical')
            ->with('medical_toast', 'Medical entry restored.')
            ->with('medical_highlight', $medical->id);
    }

    /**
     * Passport dropdown for the modal: up to 8 of the agency's HR profiles whose
     * passport starts with — or whose name contains — the typed text.
     */
    public function hrSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q']);
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q);

        $profiles = HrProfile::forAgency((int) auth()->user()->agency_id)
            ->with('passport:id,hr_profile_id,passport_number')
            ->where(function (Builder $w) use ($like) {
                $w->whereHas('passport', fn ($p) => $p->where('passport_number', 'like', $like . '%'))
                  ->orWhere('full_name_en', 'like', '%' . $like . '%');
            })
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return response()->json($profiles->map(fn (HrProfile $hr) => [
            'id'            => $hr->id,
            'file_number'   => $hr->file_number,
            'passport_no'   => $hr->passport?->passport_number,
            'full_name'     => $hr->full_name_en,
            'father_name'   => $hr->father_name,
            'date_of_birth' => $hr->date_of_birth?->format('Y-m-d'),
            'mobile_no'     => $hr->phone,
        ])->values());
    }

    /** Field values the modal form is filled with (dates as Y-m-d). */
    private function formData(Medical $m): array
    {
        return [
            'id'                  => $m->id,
            'full_name'           => $m->full_name,
            'father_name'         => $m->father_name,
            'passport_no'         => $m->passport_no,
            'date_of_birth'       => $m->date_of_birth?->format('Y-m-d'),
            'medical_center_name' => $m->medical_center_name,
            'country'             => $m->country,
            'medical_code'        => $m->medical_code,
            'mobile_no'           => $m->mobile_no,
            'medical_issue_date'  => $m->medical_issue_date?->format('Y-m-d'),
            'medical_expire_date' => $m->medical_expire_date?->format('Y-m-d'),
            'medical_status'      => $m->medical_status,
            'reference'           => $m->reference,
            'remarks'             => $m->remarks,
        ];
    }

    /**
     * Passport → identity suggestion for the Add/Edit form. HR Profile first
     * (the agency's master candidate record), then the latest earlier Medical
     * entry for the same passport (renewals). Read-only, agency-scoped.
     */
    public function lookup(Request $request): JsonResponse
    {
        $data     = $request->validate(['passport_no' => ['required', 'string', 'max:100']]);
        $agencyId = (int) auth()->user()->agency_id;
        $passport = trim($data['passport_no']);

        $hr = HrProfile::forAgency($agencyId)
            ->whereHas('passport', fn ($q) => $q->where('passport_number', $passport))
            ->latest('updated_at')
            ->first();

        if ($hr) {
            return response()->json([
                'found'         => true,
                'source'        => 'HR Profile' . ($hr->file_number ? ' #' . $hr->file_number : ''),
                'full_name'     => $hr->full_name_en,
                'father_name'   => $hr->father_name,
                'date_of_birth' => $hr->date_of_birth?->format('Y-m-d'),
                'mobile_no'     => $hr->phone,
            ]);
        }

        $prev = Medical::forAgency($agencyId)->where('passport_no', $passport)->latest('updated_at')->first();

        if ($prev) {
            return response()->json([
                'found'         => true,
                'source'        => 'Previous medical entry',
                'full_name'     => $prev->full_name,
                'father_name'   => $prev->father_name,
                'date_of_birth' => $prev->date_of_birth?->format('Y-m-d'),
                'mobile_no'     => $prev->mobile_no,
            ]);
        }

        return response()->json(['found' => false]);
    }

    /** Medical Summary for ONE entry (reference format). */
    public function printEntry(Medical $medical, PdfGeneratorService $pdf): Response
    {
        $this->authorizeAgency($medical);

        return $this->renderSummary($pdf, collect([$medical]), 'medical-' . $medical->passport_no, route('erp.medical.show', $medical));
    }

    /** Medical Summary for the whole (filtered) list — same filters as the screen. */
    public function printPdf(Request $request, PdfGeneratorService $pdf): Response
    {
        $entries = $this->filteredQuery(auth()->user()->agency_id, $this->filters($request))->get();

        return $this->renderSummary($pdf, $entries, 'medical-summary-' . now()->format('Y-m-d'), route('erp.medical', $request->query()));
    }

    /**
     * Browser preview by default (auto-opens the print dialog); ?download=1
     * pipes the SAME view through mPDF — so preview, print and PDF match.
     */
    private function renderSummary(PdfGeneratorService $pdf, Collection $entries, string $filename, string $backUrl): Response
    {
        $data = [
            'agency'    => auth()->user()->agency,
            'entries'   => $entries,
            'generated' => now(),
        ];

        if (request()->boolean('download')) {
            return $pdf->generateFromView('prints.medical-summary', $data, $filename, false, \App\Support\ErpPrintTheme::mpdfOptions());
        }

        return response()->view('prints.medical-summary', $data + [
            '_downloadUrl' => request()->fullUrlWithQuery(['download' => 1]),
            '_backUrl'     => $backUrl,
        ]);
    }

    /** Normalised list filters from the query string. */
    private function filters(Request $request): array
    {
        $status = (string) $request->query('status', '');
        $sort   = (string) $request->query('sort', 'issue_asc');

        $date = function (string $key) use ($request): string {
            $v = (string) $request->query($key, '');
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : '';
        };

        return [
            'q'       => trim((string) $request->query('q', '')),
            'from'    => $date('from'),
            'to'      => $date('to'),
            'status'  => array_key_exists($status, Medical::MEDICAL_STATUSES) ? $status : '',
            'agent'   => mb_substr(trim((string) $request->query('agent', '')), 0, 191),
            'sort'    => array_key_exists($sort, self::SORTS) ? $sort : 'issue_asc',
            'trashed' => $request->boolean('trashed') && auth()->user()->isAgencyAdmin(),
        ];
    }

    /** One query for the list, print and CSV export (always agency-scoped). */
    private function filteredQuery(int $agencyId, array $filters): Builder
    {
        $query = Medical::forAgency($agencyId)->with('createdBy:id,name');

        if ($filters['trashed']) {
            $query->onlyTrashed();
        }

        if ($filters['q'] !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $filters['q']) . '%';
            $query->where(function (Builder $w) use ($like) {
                foreach (['full_name', 'father_name', 'passport_no', 'medical_center_name', 'medical_code', 'mobile_no'] as $col) {
                    $w->orWhere($col, 'like', $like);
                }
            });
        }

        if ($filters['status'] !== '') {
            $query->where('medical_status', $filters['status']);
        }

        // Agent filter: Reference stores the agent's name.
        if ($filters['agent'] !== '') {
            $query->where('reference', $filters['agent']);
        }

        // Date range applies to the medical issue date.
        if ($filters['from'] !== '') {
            $query->whereDate('medical_issue_date', '>=', $filters['from']);
        }
        if ($filters['to'] !== '') {
            $query->whereDate('medical_issue_date', '<=', $filters['to']);
        }

        return match ($filters['sort']) {
            'issue_desc' => $query->orderByRaw('medical_issue_date IS NULL')->orderByDesc('medical_issue_date')->orderByDesc('id'),
            'expiry_asc' => $query->orderByRaw('medical_expire_date IS NULL')->orderBy('medical_expire_date')->orderBy('id'),
            'name_asc'   => $query->orderBy('full_name')->orderBy('id'),
            'status'     => $query->orderBy('medical_status')->orderBy('full_name')->orderBy('id'),
            'latest'     => $query->orderByDesc('id'),
            default      => $query->orderByRaw('medical_issue_date IS NULL')->orderBy('medical_issue_date')->orderBy('id'),
        };
    }

    /** Link to the agency's HR profile holding this passport, if any. */
    private function hrProfileIdFor(int $agencyId, string $passportNo): ?int
    {
        return HrProfile::forAgency($agencyId)
            ->whereHas('passport', fn ($q) => $q->where('passport_number', $passportNo))
            ->latest('updated_at')
            ->value('id');
    }

    /** CSV data export — staff-visible; header matches the import template. */
    public function exportCsv(Request $request): StreamedResponse
    {
        $entries = $this->filteredQuery(auth()->user()->agency_id, $this->filters($request))->get();

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            foreach ($entries as $e) {
                fputcsv($out, [
                    $e->full_name, $e->father_name, $e->passport_no,
                    optional($e->date_of_birth)->format('Y-m-d'),
                    $e->medical_center_name, $e->country, $e->medical_code,
                    optional($e->medical_issue_date)->format('Y-m-d'),
                    optional($e->medical_expire_date)->format('Y-m-d'),
                    $e->statusLabel(),
                    $e->mobile_no, $e->reference, $e->remarks,
                ]);
            }
            fclose($out);
        }, 'medical-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function importForm()
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        return view('erp.import.form', $this->importView() + ['result' => null]);
    }

    public function importTemplate(): StreamedResponse
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            fclose($out);
        }, 'medical-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function importPreview(Request $request, CsvImportService $importer)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $path   = $request->file('file')->store('erp-imports');
        $result = $importer->process(Storage::path($path), $this->importConfig(auth()->user()->agency_id));

        if ($result['fileError']) {
            Storage::delete($path);
            return redirect()->route('erp.medical.import.form')->with('error', $result['fileError']);
        }

        $request->session()->put(self::IMPORT_SESSION_KEY, $path);

        return view('erp.import.form', $this->importView() + ['result' => $result]);
    }

    public function import(Request $request, CsvImportService $importer)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $agencyId = auth()->user()->agency_id;
        $path     = $request->session()->get(self::IMPORT_SESSION_KEY);

        if (! $path || ! Storage::exists($path)) {
            return redirect()->route('erp.medical.import.form')
                ->with('error', 'Import session expired — please upload the file again.');
        }

        $result = $importer->process(Storage::path($path), $this->importConfig($agencyId));

        if (! $result['ok']) {
            return view('erp.import.form', $this->importView() + ['result' => $result])
                ->withErrors(['file' => 'Some rows are invalid — nothing was imported. Fix and re-upload.']);
        }

        DB::transaction(function () use ($result, $agencyId) {
            foreach ($result['rows'] as $row) {
                Medical::create($row['attrs'] + [
                    'agency_id'     => $agencyId,
                    'hr_profile_id' => $this->hrProfileIdFor($agencyId, $row['attrs']['passport_no']),
                    'created_by'    => auth()->id(),
                    'updated_by'    => auth()->id(),
                ]);
            }
        });

        $count = $result['total'];
        Storage::delete($path);
        $request->session()->forget(self::IMPORT_SESSION_KEY);

        return redirect()->route('erp.medical')
            ->with('success', "Imported {$count} medical " . ($count === 1 ? 'entry' : 'entries') . '.');
    }

    /** Shared-view props for the parameterized import screen. */
    private function importView(): array
    {
        $statusValues = collect(Medical::MEDICAL_STATUSES)->map(fn ($l, $k) => "$k ($l)")->implode(', ');

        return [
            'title'         => 'Import Medical — CSV',
            'heading'       => 'Import Medical entries from CSV',
            'back'          => 'erp.medical',
            'formRoute'     => 'erp.medical.import.form',
            'templateRoute' => 'erp.medical.import.template',
            'previewRoute'  => 'erp.medical.import.preview',
            'commitRoute'   => 'erp.medical.import',
            'columnsHint'   => 'full_name*, father_name*, passport_no*, date_of_birth*, medical_center_name*, country*, medical_code, medical_issue_date*, medical_expire_date*, medical_status*, mobile_no, reference, remarks',
            'legend'        => [
                "medical_status: accepts the key or its label — {$statusValues}.",
                'Age is calculated automatically from date_of_birth.',
            ],
            'previewCols'   => [
                ['label' => 'Name', 'key' => 'full_name'],
                ['label' => 'Passport', 'key' => 'passport_no'],
                ['label' => 'D.O.B', 'key' => 'date_of_birth'],
                ['label' => 'Issue Date', 'key' => 'medical_issue_date'],
                ['label' => 'Status', 'key' => 'medical_status'],
            ],
        ];
    }

    /** Import config: dates → Y-m-d, lenient status key/label, duplicate-passport notice. */
    private function importConfig(int $agencyId): array
    {
        $labelToKey = [];
        foreach (Medical::MEDICAL_STATUSES as $key => $label) {
            $labelToKey[strtolower($label)] = $key;
        }

        return [
            'headers'  => self::CSV_HEADERS,
            'rules'    => MedicalEntryRequest::baseRules(),
            'messages' => MedicalEntryRequest::baseMessages(),
            'normalize' => function (array $r) use ($labelToKey) {
                $a = array_map(fn ($v) => trim((string) $v), $r);

                foreach (['date_of_birth', 'medical_issue_date', 'medical_expire_date'] as $f) {
                    $a[$f] = CsvImportService::toYmd($a[$f] ?? '');
                }

                $st = $a['medical_status'] ?? '';
                if ($st !== '') {
                    $lower = strtolower($st);
                    if (array_key_exists($lower, Medical::MEDICAL_STATUSES)) {
                        $a['medical_status'] = $lower;
                    } elseif (isset($labelToKey[$lower])) {
                        $a['medical_status'] = $labelToKey[$lower];
                    }
                }

                foreach (self::OPTIONAL_CSV_FIELDS as $f) {
                    if (($a[$f] ?? '') === '') {
                        $a[$f] = null;
                    }
                }

                return $a;
            },
            'notices' => function (array $a) use ($agencyId) {
                $p = $a['passport_no'] ?? '';
                if ($p !== '' && Medical::forAgency($agencyId)->where('passport_no', $p)->exists()) {
                    return ["Passport {$p} already has a medical entry — added as a repeat."];
                }
                return [];
            },
        ];
    }

    private function authorizeAgency(Medical $medical): void
    {
        abort_unless($medical->agency_id === auth()->user()->agency_id, 403);
    }
}
