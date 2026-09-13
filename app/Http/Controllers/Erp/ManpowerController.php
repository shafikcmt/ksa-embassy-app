<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\RendersPrintableList;
use App\Models\Agent;
use App\Models\Delivery;
use App\Models\ManpowerCompletion;
use App\Services\CsvImportService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Manpower Complete tracker (E1). Agency-scoped log; agent_id optionally
 * links to an existing Agent (feeds the E3 Agent Khata ledger later).
 *
 * The "Delivery বাকি" counter (manpower passports not yet delivered) went live
 * with E2 once the deliveries table existed. No money math happens here.
 *
 * E7c adds CSV export (staff-visible) + import (admin-only). Import's one twist:
 * the CSV carries an `agent` NAME which the normalizer resolves to agent_id
 * (agency-scoped, case-insensitive). Blank → null; a name that matches no agent
 * in this agency is a HARD error, so the all-or-nothing gate blocks the file
 * (never silently dropped, never auto-created).
 */
class ManpowerController extends Controller
{
    use RendersPrintableList;

    private const CSV_HEADERS = ['completed_date', 'customer_name', 'passport_no', 'agent'];

    private const IMPORT_SESSION_KEY = 'erp_manpower_import_path';

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
            ['label' => '#', 'align' => 'right'], ['label' => 'BMET Date'],
            ['label' => 'Passenger'], ['label' => 'Passport'], ['label' => 'EC Number'], ['label' => 'Agent'],
        ];
        $rows = $entries->map(fn (ManpowerCompletion $e) => [
            $e->t_no, $e->completed_date->format('d M Y'),
            $e->customer_name, $e->passport_no, $e->ec_number ?: '—', $e->agent->name ?? '—',
        ])->all();

        return $this->respondPrintableList($pdf, [
            'title'    => 'Manpower Complete',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $entries->count() . ' entr' . ($entries->count() === 1 ? 'y' : 'ies'),
            'columns'  => $columns,
            'rows'     => $rows,
        ], 'manpower-' . now()->format('Y-m-d'), 'erp.manpower');
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
        return $request->validate($this->rules(auth()->user()->agency_id));
    }

    /** Single source of truth for validation — shared by manual Add and CSV import. */
    private function rules(int $agencyId): array
    {
        return [
            'completed_date' => ['required', 'date'],
            'customer_name'  => ['required', 'string', 'max:255'],
            'passport_no'    => ['required', 'string', 'max:100'],
            'ec_number'      => ['nullable', 'string', 'max:100'],
            // agent_id must belong to the caller's own agency (or be blank).
            'agent_id'       => ['nullable', Rule::exists('agents', 'id')->where('agency_id', $agencyId)],
        ];
    }

    /** CSV data export (E7c) — staff-visible; agent written as its NAME (round-trips via lookup). */
    public function exportCsv(): StreamedResponse
    {
        $entries = $this->listing(auth()->user()->agency_id);

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            foreach ($entries as $e) {
                fputcsv($out, [
                    optional($e->completed_date)->format('Y-m-d'),
                    $e->customer_name, $e->passport_no, $e->agent->name ?? '',
                ]);
            }
            fclose($out);
        }, 'manpower-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
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
        }, 'manpower-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function importPreview(Request $request, CsvImportService $importer)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $path   = $request->file('file')->store('erp-imports');
        $result = $importer->process(Storage::path($path), $this->importConfig(auth()->user()->agency_id));

        if ($result['fileError']) {
            Storage::delete($path);
            return redirect()->route('erp.manpower.import.form')->with('error', $result['fileError']);
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
            return redirect()->route('erp.manpower.import.form')
                ->with('error', 'Import session expired — please upload the file again.');
        }

        $result = $importer->process(Storage::path($path), $this->importConfig($agencyId));

        if (! $result['ok']) {
            return view('erp.import.form', $this->importView() + ['result' => $result])
                ->withErrors(['file' => 'Some rows are invalid — nothing was imported. Fix and re-upload.']);
        }

        DB::transaction(function () use ($result, $agencyId) {
            foreach ($result['rows'] as $row) {
                $data = $row['attrs'];
                unset($data['agent']); // display-only name; only agent_id is persisted
                ManpowerCompletion::create($data + [
                    'agency_id'  => $agencyId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        });

        $count = $result['total'];
        Storage::delete($path);
        $request->session()->forget(self::IMPORT_SESSION_KEY);

        return redirect()->route('erp.manpower')
            ->with('success', "Imported {$count} manpower " . ($count === 1 ? 'entry' : 'entries') . '.');
    }

    /** Shared-view props for the parameterized import screen. */
    private function importView(): array
    {
        return [
            'title'         => 'Import Manpower — CSV',
            'heading'       => 'Import Manpower entries from CSV',
            'back'          => 'erp.manpower',
            'formRoute'     => 'erp.manpower.import.form',
            'templateRoute' => 'erp.manpower.import.template',
            'previewRoute'  => 'erp.manpower.import.preview',
            'commitRoute'   => 'erp.manpower.import',
            'columnsHint'   => 'completed_date*, customer_name*, passport_no*, agent',
            'legend'        => ['agent: the agent name (must already exist for your agency). Leave blank for none; an unknown name blocks the import.'],
            'previewCols'   => [
                ['label' => 'Date', 'key' => 'completed_date'],
                ['label' => 'Customer', 'key' => 'customer_name'],
                ['label' => 'Passport', 'key' => 'passport_no'],
                ['label' => 'Agent', 'key' => 'agent'],
            ],
        ];
    }

    /**
     * Import config: date → Y-m-d; agent NAME → agent_id (agency-scoped,
     * case-insensitive). Blank → null; unmatched name → agent_id 0 which fails the
     * exists rule with a clear message (custom `messages`), blocking the whole file.
     * A duplicate passport is a non-blocking notice.
     */
    private function importConfig(int $agencyId): array
    {
        return [
            'headers'  => self::CSV_HEADERS,
            'rules'    => $this->rules($agencyId),
            'messages' => ['agent_id.exists' => 'Agent not found for this agency — create the agent first or leave the column blank.'],
            'normalize' => function (array $r) use ($agencyId) {
                $a = array_map(fn ($v) => trim((string) $v), $r);

                $a['completed_date'] = CsvImportService::toYmd($a['completed_date'] ?? '');

                // agent NAME → agent_id (agency-scoped, case-insensitive). Keep the
                // original name in 'agent' for the preview; strip it before insert.
                $name = $a['agent'] ?? '';
                if ($name === '') {
                    $a['agent_id'] = null;
                } else {
                    $id = Agent::forAgency($agencyId)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                        ->value('id');
                    $a['agent_id'] = $id ?: 0; // 0 = deliberately non-existent → exists rule fails
                }

                return $a;
            },
            'notices' => function (array $a) use ($agencyId) {
                $p = $a['passport_no'] ?? '';
                if ($p !== '' && ManpowerCompletion::forAgency($agencyId)->where('passport_no', $p)->exists()) {
                    return ["Passport {$p} already completed — added as a repeat."];
                }
                return [];
            },
        ];
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
