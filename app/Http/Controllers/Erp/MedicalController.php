<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\RendersPrintableList;
use App\Models\Medical;
use App\Services\CsvImportService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Medical tracker (E1). Agency-scoped medical-check log. Workflow status
 * only — no money math.
 *
 * Mirrors StampingController: CSV export (staff-visible) + import (admin-only,
 * dry-run preview + all-or-nothing commit) via CsvImportService and the shared
 * import view. NOTE: medical_issue_date is nullable, so — unlike Stamping —
 * there are no Y#/M# date serials (they would fatal on a null date).
 */
class MedicalController extends Controller
{
    use RendersPrintableList;

    private const CSV_HEADERS = [
        'medical_issue_date', 'full_name', 'father_name', 'passport_no',
        'medical_center_name', 'medical_code', 'medical_expire_date', 'medical_status',
    ];

    private const IMPORT_SESSION_KEY = 'erp_medical_import_path';

    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        return view('erp.medical.index', [
            'entries'  => $this->listing($agencyId),
            'statuses' => Medical::MEDICAL_STATUSES,
        ]);
    }

    /** Print the full module list (E7a) — reuses the EXACT index() query. */
    public function printPdf(PdfGeneratorService $pdf)
    {
        $entries = $this->listing(auth()->user()->agency_id);

        $columns = [
            ['label' => 'Issue Date'], ['label' => 'Name'], ['label' => 'Father'], ['label' => 'Passport'],
            ['label' => 'Medical Center'], ['label' => 'Code'], ['label' => 'Expire Date'], ['label' => 'Status'],
        ];
        $rows = $entries->map(fn (Medical $e) => [
            optional($e->medical_issue_date)->format('d M Y') ?: '—',
            $e->full_name, $e->father_name, $e->passport_no,
            $e->medical_center_name ?: '—', $e->medical_code ?: '—',
            optional($e->medical_expire_date)->format('d M Y') ?: '—', $e->statusLabel(),
        ])->all();

        return $this->respondPrintableList($pdf, [
            'title'    => 'Medical',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $entries->count() . ' entr' . ($entries->count() === 1 ? 'y' : 'ies'),
            'columns'  => $columns,
            'rows'     => $rows,
        ], 'medical-' . now()->format('Y-m-d'), 'erp.medical');
    }

    /** Shared listing used by both index() and printPdf() (oldest-first, null dates last). */
    private function listing(int $agencyId): Collection
    {
        return Medical::forAgency($agencyId)
            ->with('createdBy:id,name')
            ->orderByRaw('medical_issue_date IS NULL')->orderBy('medical_issue_date')->orderBy('id')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Medical::create($data + [
            'agency_id'  => auth()->user()->agency_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('erp.medical')->with('success', 'Medical entry added.');
    }

    public function update(Request $request, Medical $medical)
    {
        $this->authorizeAgency($medical);

        $medical->update($this->validated($request) + ['updated_by' => auth()->id()]);

        return redirect()->route('erp.medical')->with('success', 'Medical entry updated.');
    }

    public function destroy(Medical $medical)
    {
        $this->authorizeAgency($medical);

        $medical->delete();

        return redirect()->route('erp.medical')->with('success', 'Medical entry deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate($this->rules());
    }

    /** Single source of truth for validation — shared by manual Add and CSV import. */
    private function rules(): array
    {
        return [
            'medical_issue_date'  => ['nullable', 'date'],
            'full_name'           => ['required', 'string', 'max:255'],
            'father_name'         => ['required', 'string', 'max:255'],
            'passport_no'         => ['required', 'string', 'max:100'],
            'medical_center_name' => ['nullable', 'string', 'max:255'],
            'medical_code'        => ['nullable', 'string', 'max:100'],
            'medical_expire_date' => ['nullable', 'date'],
            'medical_status'      => ['required', Rule::in(array_keys(Medical::MEDICAL_STATUSES))],
        ];
    }

    /** CSV data export — staff-visible; header matches the import template. */
    public function exportCsv(): StreamedResponse
    {
        $entries = $this->listing(auth()->user()->agency_id);

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            foreach ($entries as $e) {
                fputcsv($out, [
                    optional($e->medical_issue_date)->format('Y-m-d'),
                    $e->full_name, $e->father_name, $e->passport_no,
                    $e->medical_center_name, $e->medical_code,
                    optional($e->medical_expire_date)->format('Y-m-d'), $e->statusLabel(),
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
                    'agency_id'  => $agencyId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
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
            'columnsHint'   => 'medical_issue_date, full_name*, father_name*, passport_no*, medical_center_name, medical_code, medical_expire_date, medical_status*',
            'legend'        => ["medical_status: accepts the key or its label — {$statusValues}."],
            'previewCols'   => [
                ['label' => 'Issue Date', 'key' => 'medical_issue_date'],
                ['label' => 'Name', 'key' => 'full_name'],
                ['label' => 'Passport', 'key' => 'passport_no'],
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
            'headers' => self::CSV_HEADERS,
            'rules'   => $this->rules(),
            'normalize' => function (array $r) use ($labelToKey) {
                $a = array_map(fn ($v) => trim((string) $v), $r);

                $a['medical_issue_date']  = CsvImportService::toYmd($a['medical_issue_date'] ?? '');
                $a['medical_expire_date'] = CsvImportService::toYmd($a['medical_expire_date'] ?? '');

                $st = $a['medical_status'] ?? '';
                if ($st !== '') {
                    $lower = strtolower($st);
                    if (array_key_exists($lower, Medical::MEDICAL_STATUSES)) {
                        $a['medical_status'] = $lower;
                    } elseif (isset($labelToKey[$lower])) {
                        $a['medical_status'] = $labelToKey[$lower];
                    }
                }

                foreach (['medical_issue_date', 'medical_expire_date', 'medical_center_name', 'medical_code'] as $f) {
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
