<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\RendersPrintableList;
use App\Models\MofaEntry;
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
 * ERP MOFA Entry tracker (E1). Agency-scoped log with a live "Stamping বাকি"
 * counter (MOFA passports not yet stamped). No money math — payment_method is
 * a categorical tag only.
 *
 * E7b adds CSV data-export (staff-visible) + CSV import (admin-only) with a
 * dry-run preview and an all-or-nothing commit. The import reuses the SAME
 * rules() as manual Add, so it can never bypass validation.
 */
class MofaEntryController extends Controller
{
    use RendersPrintableList;

    /** Import/export column order — also the downloadable template + export header. */
    private const CSV_HEADERS = [
        'mofa_date', 'mofa_number', 'visa_serial', 'full_name', 'passport_no',
        'reference_name', 'payment_method', 'whatsapp_number', 'payment_note',
    ];

    /** Session key holding the stashed upload path between preview and commit. */
    private const IMPORT_SESSION_KEY = 'erp_mofa_import_path';

    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $entries = $this->listing($agencyId);

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

    /** Print the full module list (E7a) — reuses the EXACT index() query. */
    public function printPdf(PdfGeneratorService $pdf)
    {
        $entries = $this->listing(auth()->user()->agency_id);

        $columns = [
            ['label' => 'Y#', 'align' => 'right'], ['label' => 'M#', 'align' => 'right'],
            ['label' => 'Date'], ['label' => 'MOFA #'], ['label' => 'Visa Number'], ['label' => 'ID Number'],
            ['label' => 'Name'], ['label' => 'Passport'], ['label' => 'Reference'], ['label' => 'Payment'],
        ];
        $rows = $entries->map(fn (MofaEntry $e) => [
            $e->y_no, $e->m_no, $e->mofa_date->format('d M Y'),
            $e->mofa_number ?: '—', $e->visa_serial ?: '—', $e->id_number ?: '—',
            $e->full_name, $e->passport_no, $e->reference_name ?: '—', $e->paymentMethodLabel() ?: '—',
        ])->all();

        return $this->respondPrintableList($pdf, [
            'title'    => 'MOFA Entries',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $entries->count() . ' entr' . ($entries->count() === 1 ? 'y' : 'ies'),
            'columns'  => $columns,
            'rows'     => $rows,
        ], 'mofa-entries-' . now()->format('Y-m-d'), 'erp.mofa');
    }

    /**
     * Shared listing used by both index() and printPdf(): oldest-first, assigning
     * chronological Y#/M#/Total ordinals (1 = oldest) shown top-to-bottom.
     */
    private function listing(int $agencyId): Collection
    {
        $entries = MofaEntry::forAgency($agencyId)
            ->with('createdBy:id,name')
            ->orderBy('mofa_date')->orderBy('id')
            ->get();

        $this->assignSerials($entries, 'mofa_date');

        return $entries;
    }

    /**
     * CSV data export (E7b) — staff-visible (read-only, matches Print's gating).
     * Reuses the EXACT listing() query and emits the SAME header the import
     * template + parser use, so an exported file round-trips straight back in.
     * payment_method is written as its human label (the lenient importer maps it
     * back to the key); nullable fields are blank, dates are Y-m-d for clean re-import.
     */
    public function exportCsv(): StreamedResponse
    {
        $entries = $this->listing(auth()->user()->agency_id);

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            foreach ($entries as $e) {
                fputcsv($out, [
                    optional($e->mofa_date)->format('Y-m-d'),
                    $e->mofa_number, $e->visa_serial, $e->full_name, $e->passport_no,
                    $e->reference_name, $e->paymentMethodLabel(), $e->whatsapp_number, $e->payment_note,
                ]);
            }
            fclose($out);
        }, 'mofa-entries-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** GET — the empty import screen (upload form). Admin-only. */
    public function importForm()
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        return view('erp.mofa.import', ['result' => null, 'methods' => MofaEntry::PAYMENT_METHODS]);
    }

    /** GET — a header-only CSV template. Admin-only. */
    public function importTemplate(): StreamedResponse
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            fclose($out);
        }, 'mofa-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * POST — DRY-RUN preview. Validates every row against rules(); writes NOTHING
     * to the DB. Stashes the raw upload so the commit step can re-validate the
     * original bytes. Admin-only.
     */
    public function importPreview(Request $request, CsvImportService $importer)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $path   = $request->file('file')->store('erp-imports');
        $result = $importer->process(Storage::path($path), $this->importConfig(auth()->user()->agency_id));

        if ($result['fileError']) {
            Storage::delete($path);
            return redirect()->route('erp.mofa.import.form')->with('error', $result['fileError']);
        }

        $request->session()->put(self::IMPORT_SESSION_KEY, $path);

        return view('erp.mofa.import', ['result' => $result, 'methods' => MofaEntry::PAYMENT_METHODS]);
    }

    /**
     * POST — COMMIT. Re-parses & re-validates the stashed file (commit-time
     * validation is authoritative), then inserts ALL rows in ONE transaction:
     * if anything is invalid, nothing is written (all-or-nothing). Admin-only,
     * and subscription-gated at the route (matching store()).
     */
    public function import(Request $request, CsvImportService $importer)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $agencyId = auth()->user()->agency_id;
        $path     = $request->session()->get(self::IMPORT_SESSION_KEY);

        if (! $path || ! Storage::exists($path)) {
            return redirect()->route('erp.mofa.import.form')
                ->with('error', 'Import session expired — please upload the file again.');
        }

        $result = $importer->process(Storage::path($path), $this->importConfig($agencyId));

        // All-or-nothing: refuse the whole file if any row is invalid.
        if (! $result['ok']) {
            return view('erp.mofa.import', ['result' => $result, 'methods' => MofaEntry::PAYMENT_METHODS])
                ->withErrors(['file' => 'Some rows are invalid — nothing was imported. Fix and re-upload.']);
        }

        DB::transaction(function () use ($result, $agencyId) {
            foreach ($result['rows'] as $row) {
                MofaEntry::create($row['attrs'] + [
                    'agency_id'  => $agencyId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        });

        $count = $result['total'];
        Storage::delete($path);
        $request->session()->forget(self::IMPORT_SESSION_KEY);

        return redirect()->route('erp.mofa')
            ->with('success', "Imported {$count} MOFA " . ($count === 1 ? 'entry' : 'entries') . '.');
    }

    /**
     * Import config for CsvImportService: shared rules() + a normaliser (trim,
     * lenient enum key/label mapping, date → Y-m-d) + a duplicate-passport notice
     * (non-blocking — MOFA legitimately repeats, so a repeat is imported, not skipped).
     */
    private function importConfig(int $agencyId): array
    {
        $labelToKey = [];
        foreach (MofaEntry::PAYMENT_METHODS as $key => $label) {
            $labelToKey[strtolower($label)] = $key;
        }

        return [
            'headers' => self::CSV_HEADERS,
            'rules'   => $this->rules(),
            'normalize' => function (array $r) use ($labelToKey) {
                $a = array_map(fn ($v) => trim((string) $v), $r);

                // payment_method: accept key OR human label (case-insensitive) → key.
                $pm = $a['payment_method'] ?? '';
                if ($pm === '') {
                    $a['payment_method'] = null;
                } else {
                    $lower = strtolower($pm);
                    if (array_key_exists($lower, MofaEntry::PAYMENT_METHODS)) {
                        $a['payment_method'] = $lower;                 // matched a key
                    } elseif (isset($labelToKey[$lower])) {
                        $a['payment_method'] = $labelToKey[$lower];    // matched a label
                    } else {
                        $a['payment_method'] = $pm;                    // leave → Rule::in fails clearly
                    }
                }

                $a['mofa_date'] = $this->normalizeDate($a['mofa_date'] ?? '');

                foreach (['mofa_number', 'visa_serial', 'reference_name', 'whatsapp_number', 'payment_note'] as $f) {
                    if (($a[$f] ?? '') === '') {
                        $a[$f] = null;
                    }
                }

                return $a;
            },
            'notices' => function (array $a) use ($agencyId) {
                $p = $a['passport_no'] ?? '';
                if ($p !== '' && MofaEntry::forAgency($agencyId)->where('passport_no', $p)->exists()) {
                    return ["Passport {$p} already exists — added as a repeat MOFA."];
                }
                return [];
            },
        ];
    }

    /** Normalise common date inputs to Y-m-d; leave unparseable values for the rule to reject. */
    private function normalizeDate(string $v): string
    {
        $v = trim($v);
        if ($v === '') {
            return '';
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $fmt) {
            $d = \DateTime::createFromFormat('!' . $fmt, $v);
            if ($d && $d->format($fmt) === $v) {
                return $d->format('Y-m-d');
            }
        }

        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : $v;
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $agencyId = auth()->user()->agency_id;

        // Warn-and-confirm on a duplicate passport. Same forAgency + passport_no
        // predicate as the CSV-import notice; .first() replaces .exists() only so the
        // message can show the existing entry's date. Skipped once the user confirms.
        if (! $request->boolean('confirm_duplicate')) {
            $existing = MofaEntry::forAgency($agencyId)
                ->where('passport_no', $data['passport_no'])
                ->orderByDesc('mofa_date')
                ->first();

            if ($existing) {
                return back()->withInput()->with(
                    'duplicate_warning',
                    "A MOFA entry already exists for this passport (added on {$existing->mofa_date->format('d M Y')})."
                );
            }
        }

        MofaEntry::create($data + [
            'agency_id'  => $agencyId,
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
        return $request->validate($this->rules());
    }

    /**
     * The single source of truth for MOFA field validation — used by manual Add
     * (validated()) AND the CSV import (per-row), so import can never accept what
     * the form would reject.
     */
    private function rules(): array
    {
        return [
            'mofa_date'       => ['required', 'date'],
            'mofa_number'     => ['nullable', 'string', 'max:100'],
            'visa_serial'     => ['nullable', 'string', 'max:100'],
            'id_number'       => ['nullable', 'string', 'max:100'],
            'full_name'       => ['required', 'string', 'max:255'],
            'passport_no'     => ['required', 'string', 'max:100'],
            'reference_name'  => ['nullable', 'string', 'max:255'],
            'payment_method'  => ['nullable', Rule::in(array_keys(MofaEntry::PAYMENT_METHODS))],
            'whatsapp_number' => ['nullable', 'string', 'max:40'],
            'payment_note'    => ['nullable', 'string', 'max:1000'],
        ];
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
