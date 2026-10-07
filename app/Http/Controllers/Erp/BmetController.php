<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\BmetEntryRequest;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\BmetEntry;
use App\Models\HrProfile;
use App\Services\PdfGeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BmetController extends Controller
{
    public const COLUMNS = ['SL', 'Passenger Name', "Father's Name", 'PP No', 'Visa No', 'Id No', 'EC No', 'EC Date', 'Reference', 'Remarks'];

    public const FIELDS = ['full_name', 'father_name', 'passport_number', 'visa_number', 'id_number', 'ec_number', 'ec_date', 'reference', 'remarks'];

    public const SORTS = ['date_desc' => 'Date · newest first', 'date_asc' => 'Date · oldest first', 'name_asc' => 'Name · A–Z', 'name_desc' => 'Name · Z–A', 'status_asc' => 'Status · A–Z', 'status_desc' => 'Status · Z–A'];

    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:100', 'status' => ['nullable', Rule::in(array_keys(BmetEntry::STATUSES))],
            'agent_id' => ['nullable', 'integer', Rule::exists('agents', 'id')->where('agency_id', $request->user()->agency_id)],
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d'.($request->filled('from') ? '|after_or_equal:from' : ''),
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
        ]);
    }

    private function query(Request $request): Builder
    {
        $filters = $this->filters($request);
        $query = BmetEntry::forAgency((int) $request->user()->agency_id)->with('agent:id,name');
        if ($q = trim($filters['q'] ?? '')) {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('customer_name', 'like', $like)->orWhere('passport_no', strtoupper($q))->orWhere('ec_number', 'like', $like)->orWhere('reference', 'like', $like));
        }
        if ($filters['status'] ?? null) {
            $query->withStatus($filters['status']);
        }
        if ($filters['agent_id'] ?? null) {
            $query->where('agent_id', $filters['agent_id']);
        }
        if ($filters['from'] ?? null) {
            $query->whereDate('completed_date', '>=', $filters['from']);
        }
        if ($filters['to'] ?? null) {
            $query->whereDate('completed_date', '<=', $filters['to']);
        }
        [$sort, $direction] = explode('_', $filters['sort'] ?? 'date_desc');
        if ($sort === 'status') {
            $query->orderByRaw("CASE WHEN status = 'cleared' AND ec_expiry_date < ? THEN 'expired' ELSE status END {$direction}", [today()->toDateString()]);
        } else {
            $query->orderBy($sort === 'name' ? 'customer_name' : 'completed_date', $direction);
        }

        return $query->orderByDesc('id');
    }

    public function index(Request $request)
    {
        $base = BmetEntry::forAgency((int) $request->user()->agency_id);
        $stats = ['total' => $base->count()];
        foreach (array_keys(BmetEntry::STATUSES) as $status) {
            $stats[$status] = (clone $base)->withStatus($status)->count();
        }

        return view('erp.bmet.index', [
            'entries' => $this->query($request)->paginate(20)->withQueryString(), 'stats' => $stats,
            'agents' => Agent::forAgency((int) $request->user()->agency_id)->orderBy('name')->get(['id', 'name', 'phone', 'address', 'status']),
        ]);
    }

    public function create()
    {
        return redirect()->route('erp.bmet.index', ['add' => 1]);
    }

    private function authorizeEntry(BmetEntry $entry): void
    {
        abort_unless((int) $entry->agency_id === (int) auth()->user()->agency_id, 403);
    }

    public function show(Request $request, BmetEntry $bmetEntry)
    {
        $this->authorizeEntry($bmetEntry);
        if ($request->expectsJson()) {
            $data = ['id' => $bmetEntry->id, 'agent_id' => $bmetEntry->agent_id, 'status' => $bmetEntry->status];
            foreach (self::FIELDS as $field) {
                $data[$field] = self::value($bmetEntry, $field, 'Y-m-d');
            }

            return response()->json($data);
        }

        return view('erp.bmet.show', ['entry' => $bmetEntry->load('agent')]);
    }

    public function edit(BmetEntry $bmetEntry)
    {
        $this->authorizeEntry($bmetEntry);

        return redirect()->route('erp.bmet.index', ['edit' => $bmetEntry->id]);
    }

    public function store(BmetEntryRequest $request)
    {
        return $this->save($request, new BmetEntry(['agency_id' => $request->user()->agency_id, 'created_by' => $request->user()->id]));
    }

    public function update(BmetEntryRequest $request, BmetEntry $bmetEntry)
    {
        $this->authorizeEntry($bmetEntry);

        return $this->save($request, $bmetEntry);
    }

    private function save(BmetEntryRequest $request, BmetEntry $entry)
    {
        DB::transaction(function () use ($request, $entry) {
            Agency::whereKey($request->user()->agency_id)->lockForUpdate()->firstOrFail();
            $data = $request->validated();
            $duplicate = BmetEntry::forAgency((int) $entry->agency_id)->where('passport_no', $data['passport_number'])->when($entry->exists, fn ($q) => $q->whereKeyNot($entry->id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['passport_number' => 'Passport number already exists']);
            }
            if (($data['status'] ?? 'auto') === 'auto') {
                $data['status'] = filled($data['ec_number'] ?? null) ? 'cleared' : 'pending';
            }
            $hr = HrProfile::forAgency((int) $entry->agency_id)->whereHas('passport', fn ($q) => $q->where('passport_number', $data['passport_number']))->first();
            $entry->fill($data)->fill(['hr_profile_id' => $hr?->id, 'updated_by' => $request->user()->id])->save();
        });
        session()->flash('bmet_toast', 'BMET entry saved successfully');

        return $request->expectsJson() ? response()->json(['ok' => true, 'id' => $entry->id]) : redirect()->route('erp.bmet.index');
    }

    public function destroy(BmetEntry $bmetEntry)
    {
        $this->authorizeEntry($bmetEntry);
        $bmetEntry->delete();

        return redirect()->route('erp.bmet.index')->with('bmet_toast', 'BMET entry deleted successfully');
    }

    public function hrSearch(Request $request)
    {
        $q = $request->validate(['q' => 'required|string|min:2|max:100'])['q'];
        $profiles = HrProfile::forAgency((int) $request->user()->agency_id)->with(['passport', 'visa'])
            ->whereHas('passport', fn ($w) => $w->where('passport_number', 'like', addcslashes($q, '%_\\').'%'))->orderBy('full_name_en')->limit(8)->get();

        return response()->json($profiles->map(fn ($p) => [
            'id' => $p->id, 'full_name' => $p->full_name_en, 'father_name' => $p->father_name,
            'passport_number' => $p->passport->passport_number, 'visa_number' => $p->visa?->visa_number, 'id_number' => $p->visa?->sponsor_id,
        ]));
    }

    public static function value(BmetEntry $entry, string $field, string $format = 'd-M-Y')
    {
        $value = $entry->$field;

        return $value instanceof \DateTimeInterface ? $value->format($format) : $value;
    }

    public function exportCsv(Request $request)
    {
        $query = $this->query($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::COLUMNS);
            foreach ($query->cursor() as $i => $entry) {
                $row = [$i + 1];
                foreach (self::FIELDS as $field) {
                    $value = (string) self::value($entry, $field);
                    $row[] = preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
                }
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'bmet-summary.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function printPdf(Request $request, PdfGeneratorService $pdf, ?BmetEntry $bmetEntry = null)
    {
        if ($bmetEntry) {
            $this->authorizeEntry($bmetEntry);
        }
        $data = ['entries' => $bmetEntry ? collect([$bmetEntry]) : $this->query($request)->get(), 'agency' => $request->user()->agency, 'generated' => now()];
        // Default → browser print preview (opens the print dialog); ?download=1 → mPDF file.
        if (! $request->boolean('download')) {
            return view('prints.bmet-summary', $data + [
                '_downloadUrl' => $request->fullUrlWithQuery(['download' => 1, 'preview' => null]),
                '_backUrl'     => $bmetEntry ? route('erp.bmet.show', $bmetEntry) : url()->previous(route('erp.bmet.index')),
            ]);
        }

        return $pdf->generateFromView('prints.bmet-summary', $data, 'bmet-clearance-summary', false, \App\Support\ErpPrintTheme::mpdfOptions());
    }
}
