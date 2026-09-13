<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\RendersPrintableList;
use App\Models\Agent;
use App\Models\ManpowerCompletion;
use App\Models\Stamping;
use App\Services\CsvImportService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Stamping tracker (E1). Agency-scoped log with a live "Manpower বাকি"
 * counter (stamped passports not yet completed as manpower). No money math.
 *
 * E7c adds CSV export (staff-visible) + import (admin-only, dry-run preview +
 * all-or-nothing commit), reusing CsvImportService and the shared import view.
 */
class StampingController extends Controller
{
    use RendersPrintableList;

    private const CSV_HEADERS = [
        'stamp_date', 'visa_serial', 'full_name', 'passport_no',
        'visa_number', 'id_number', 'reference', 'status',
    ];

    private const IMPORT_SESSION_KEY = 'erp_stamping_import_path';

    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $entries = $this->listing($agencyId);

        // "Manpower বাকি": stamped passports with no matching manpower row.
        $manpowerBaki = Stamping::forAgency($agencyId)
            ->whereNotIn('passport_no', ManpowerCompletion::forAgency($agencyId)->select('passport_no'))
            ->count();

        // Agents for the "auto-fill Reference" dropdown — scoped to THIS agency
        // only (multi-tenancy), never other agencies' agents. Not persisted on
        // the stamping row; the selection just pre-fills the reference text.
        $agents = Agent::forAgency($agencyId)->active()->orderBy('name')->get(['id', 'name']);

        return view('erp.stamping.index', [
            'entries'      => $entries,
            'manpowerBaki' => $manpowerBaki,
            'statuses'     => Stamping::STATUSES,
            'agents'       => $agents,
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

        return $this->respondPrintableList($pdf, [
            'title'    => 'Stamping',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $entries->count() . ' entr' . ($entries->count() === 1 ? 'y' : 'ies'),
            'columns'  => $columns,
            'rows'     => $rows,
        ], 'stamping-' . now()->format('Y-m-d'), 'erp.stamping');
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
        return $request->validate($this->rules());
    }

    /** Single source of truth for validation — shared by manual Add and CSV import. */
    private function rules(): array
    {
        return [
            'stamp_date'  => ['required', 'date'],
            'visa_serial' => ['nullable', 'string', 'max:100'],
            'full_name'   => ['required', 'string', 'max:255'],
            'passport_no' => ['required', 'string', 'max:100'],
            'visa_number' => ['nullable', 'string', 'max:100'],
            'id_number'   => ['nullable', 'string', 'max:100'],
            'reference'   => ['nullable', 'string', 'max:255'],
            'status'      => ['required', Rule::in(array_keys(Stamping::STATUSES))],
        ];
    }

    /** CSV data export (E7c) — staff-visible; header matches the import template. */
    public function exportCsv(): StreamedResponse
    {
        $entries = $this->listing(auth()->user()->agency_id);

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            foreach ($entries as $e) {
                fputcsv($out, [
                    optional($e->stamp_date)->format('Y-m-d'),
                    $e->visa_serial, $e->full_name, $e->passport_no,
                    $e->visa_number, $e->id_number, $e->reference, $e->statusLabel(),
                ]);
            }
            fclose($out);
        }, 'stamping-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
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
        }, 'stamping-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function importPreview(Request $request, CsvImportService $importer)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $path   = $request->file('file')->store('erp-imports');
        $result = $importer->process(Storage::path($path), $this->importConfig(auth()->user()->agency_id));

        if ($result['fileError']) {
            Storage::delete($path);
            return redirect()->route('erp.stamping.import.form')->with('error', $result['fileError']);
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
            return redirect()->route('erp.stamping.import.form')
                ->with('error', 'Import session expired — please upload the file again.');
        }

        $result = $importer->process(Storage::path($path), $this->importConfig($agencyId));

        if (! $result['ok']) {
            return view('erp.import.form', $this->importView() + ['result' => $result])
                ->withErrors(['file' => 'Some rows are invalid — nothing was imported. Fix and re-upload.']);
        }

        DB::transaction(function () use ($result, $agencyId) {
            foreach ($result['rows'] as $row) {
                Stamping::create($row['attrs'] + [
                    'agency_id'  => $agencyId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        });

        $count = $result['total'];
        Storage::delete($path);
        $request->session()->forget(self::IMPORT_SESSION_KEY);

        return redirect()->route('erp.stamping')
            ->with('success', "Imported {$count} stamping " . ($count === 1 ? 'entry' : 'entries') . '.');
    }

    /** Shared-view props for the parameterized import screen. */
    private function importView(): array
    {
        $statusValues = collect(Stamping::STATUSES)->map(fn ($l, $k) => "$k ($l)")->implode(', ');

        return [
            'title'         => 'Import Stamping — CSV',
            'heading'       => 'Import Stamping entries from CSV',
            'back'          => 'erp.stamping',
            'formRoute'     => 'erp.stamping.import.form',
            'templateRoute' => 'erp.stamping.import.template',
            'previewRoute'  => 'erp.stamping.import.preview',
            'commitRoute'   => 'erp.stamping.import',
            'columnsHint'   => 'stamp_date*, visa_serial, full_name*, passport_no*, visa_number, id_number, reference, status*',
            'legend'        => ["status: accepts the key or its label — {$statusValues}."],
            'previewCols'   => [
                ['label' => 'Date', 'key' => 'stamp_date'],
                ['label' => 'Name', 'key' => 'full_name'],
                ['label' => 'Passport', 'key' => 'passport_no'],
                ['label' => 'Status', 'key' => 'status'],
            ],
        ];
    }

    /** Import config: date → Y-m-d, lenient status key/label, duplicate-passport notice. */
    private function importConfig(int $agencyId): array
    {
        $labelToKey = [];
        foreach (Stamping::STATUSES as $key => $label) {
            $labelToKey[strtolower($label)] = $key;
        }

        return [
            'headers' => self::CSV_HEADERS,
            'rules'   => $this->rules(),
            'normalize' => function (array $r) use ($labelToKey) {
                $a = array_map(fn ($v) => trim((string) $v), $r);

                $a['stamp_date'] = CsvImportService::toYmd($a['stamp_date'] ?? '');

                $st = $a['status'] ?? '';
                if ($st !== '') {
                    $lower = strtolower($st);
                    if (array_key_exists($lower, Stamping::STATUSES)) {
                        $a['status'] = $lower;
                    } elseif (isset($labelToKey[$lower])) {
                        $a['status'] = $labelToKey[$lower];
                    }
                }

                foreach (['visa_serial', 'visa_number', 'id_number', 'reference'] as $f) {
                    if (($a[$f] ?? '') === '') {
                        $a[$f] = null;
                    }
                }

                return $a;
            },
            'notices' => function (array $a) use ($agencyId) {
                $p = $a['passport_no'] ?? '';
                if ($p !== '' && Stamping::forAgency($agencyId)->where('passport_no', $p)->exists()) {
                    return ["Passport {$p} already stamped — added as a repeat."];
                }
                return [];
            },
        ];
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
