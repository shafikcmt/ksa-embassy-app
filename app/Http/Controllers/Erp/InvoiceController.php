<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\HrProfile;
use App\Models\Invoice;
use App\Services\BarcodeService;
use App\Services\InvoiceService;
use App\Services\PdfGeneratorService;
use App\Support\NumberToWords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * ERP Invoices — agency-scoped multi-line invoices with live totals.
 *
 * Tenancy: every query is scoped to the caller's agency_id and every
 * row action re-checks ownership (authorizeAgency → 403). Linked HR/agent ids
 * are validated with agency-scoped exists rules, so another agency's records can
 * never be attached.
 *
 * Permissions (same invariants as the rest of the ERP suite):
 *   view / print / PDF .......... any access_erp staff
 *   create / edit (draft|pending) any access_erp staff (create needs active subscription — route)
 *   mark as paid ................ agency admin OR erp_receive_payment grant
 *   cancel ...................... agency admin
 *   delete (soft) ............... InvoicePolicy: draft → admin or its creator;
 *                                 pending/paid/cancelled → agency admin only
 *
 * Due date is no longer shown or entered; the column and the "overdue" filter
 * branch stay so old rows and bookmarked URLs keep working.
 *
 * All money + numbering + lifecycle writes go through InvoiceService.
 */
class InvoiceController extends Controller
{
    private const MONEY = '/^\d{1,12}(\.\d{1,2})?$/';
    private const PRICE = '/^\d{1,9}(\.\d{1,2})?$/';

    public const SORTS = [
        'date_desc'   => 'Newest first',
        'date_asc'    => 'Oldest first',
        'amount_desc' => 'Amount: high → low',
        'amount_asc'  => 'Amount: low → high',
        'number_desc' => 'Invoice no.',
    ];

    public function __construct(private InvoiceService $invoices)
    {
    }

    public function index(Request $request)
    {
        $agencyId = auth()->user()->agency_id;

        $filters = [
            'q'      => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'from'   => (string) $request->query('from', ''),
            'to'     => (string) $request->query('to', ''),
            'sort'   => array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'date_desc',
        ];

        $query = Invoice::forAgency($agencyId)->withCount('items');

        if ($filters['q'] !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $filters['q']) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('invoice_number', 'like', $like)
                    ->orWhere('bill_to_name', 'like', $like)
                    ->orWhere('bill_to_phone', 'like', $like)
                    ->orWhereHas('agent', fn ($a) => $a->where('name', 'like', $like))
                    ->orWhereHas('items', fn ($i) => $i->where('passenger_name', 'like', $like)
                        ->orWhere('passport_no', 'like', $like)
                        ->orWhereHas('hrProfile', fn ($h) => $h->where('full_name_en', 'like', $like)));
            });
        }

        // 'overdue' is no longer offered in the UI; kept so old bookmarked URLs still work.
        if ($filters['status'] === 'overdue') {
            $query->where('status', 'pending')->whereNotNull('due_date')->whereDate('due_date', '<', today());
        } elseif (array_key_exists($filters['status'], Invoice::STATUSES)) {
            $query->where('status', $filters['status']);
        }

        if ($this->isDate($filters['from'])) {
            $query->whereDate('invoice_date', '>=', $filters['from']);
        }
        if ($this->isDate($filters['to'])) {
            $query->whereDate('invoice_date', '<=', $filters['to']);
        }

        match ($filters['sort']) {
            'date_asc'    => $query->orderBy('invoice_date')->orderBy('id'),
            'amount_desc' => $query->orderByDesc('total_amount')->orderByDesc('id'),
            'amount_asc'  => $query->orderBy('total_amount')->orderBy('id'),
            'number_desc' => $query->orderByDesc('number_year')->orderByDesc('number_seq'),
            default       => $query->orderByDesc('invoice_date')->orderByDesc('id'),
        };

        $invoices = $query->with('agent:id,name')->paginate(12)->withQueryString();

        // Summary strip — whole agency (unfiltered), grouped per currency so
        // BDT and SAR amounts are never added together.
        $summary = Invoice::forAgency($agencyId)
            ->selectRaw('status, currency, COUNT(*) as cnt, SUM(total_amount) as total')
            ->groupBy('status', 'currency')
            ->get();

        return view('erp.invoices.index', [
            'invoices' => $invoices,
            'filters'  => $filters,
            'sorts'    => self::SORTS,
            'statuses' => Invoice::STATUSES,
            'summary'  => $summary,
        ]);
    }

    /**
     * JSON lookup used by the payment-voucher form to auto-fill payee details.
     * Agency-scoped, LIKE wildcards escaped, min 2 chars, max 10 results.
     * Falls back to the linked agent's name/phone/address when bill-to is blank.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q) . '%';

        $invoices = Invoice::forAgency(auth()->user()->agency_id)
            ->where(fn ($w) => $w->where('invoice_number', 'like', $like)
                ->orWhere('bill_to_name', 'like', $like)
                ->orWhereHas('agent', fn ($a) => $a->where('name', 'like', $like)))
            ->with('agent:id,name,phone,address')
            ->orderByDesc('invoice_date')->orderByDesc('id')
            ->limit(10)
            ->get();

        return response()->json($invoices->map(fn (Invoice $inv) => [
            'id'              => $inv->id,
            'invoice_number'  => $inv->invoice_number,
            'bill_to_name'    => $inv->bill_to_name ?: $inv->agent?->name,
            'bill_to_phone'   => $inv->bill_to_phone ?: $inv->agent?->phone,
            'bill_to_address' => $inv->bill_to_address ?: $inv->agent?->address,
            'agent_name'      => $inv->agent?->name,
            'status'          => $inv->statusLabel(),
            'total'           => $inv->currency . ' ' . number_format((float) $inv->total_amount, 2),
        ])->values());
    }

    public function create()
    {
        $agencyId = auth()->user()->agency_id;

        return view('erp.invoices.create', $this->formData($agencyId) + [
            'invoice'     => new Invoice([
                'invoice_date'  => today(),
                'status'        => 'pending',
                'currency'      => 'BDT',
                'tax_type'      => 'none',
                'discount_type' => 'none',
            ]),
            'nextNumber'  => $this->invoices->peekNextNumber($agencyId, (int) now()->year),
        ]);
    }

    public function store(Request $request)
    {
        $agencyId = auth()->user()->agency_id;
        [$data, $items] = $this->validated($request, $agencyId);

        $invoice = $this->invoices->save(null, $agencyId, $data, $items, auth()->user());

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeAgency($invoice);
        $invoice->load(['items.hrProfile.passport', 'agent', 'createdBy:id,name', 'updatedBy:id,name', 'paidBy:id,name']);

        $timeline = AuditLog::with('user:id,name')
            ->where('agency_id', $invoice->agency_id)
            ->where('auditable_type', Invoice::class)
            ->where('auditable_id', $invoice->id)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit(30)
            ->get();

        return view('erp.invoices.show', [
            'invoice'        => $invoice,
            'timeline'       => $timeline,
            'paymentMethods' => Invoice::PAYMENT_METHODS,
            'canPay'         => $this->canReceivePayment(),
            'isAdmin'        => auth()->user()->isAgencyAdmin(),
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $this->authorizeAgency($invoice);

        if ($invoice->isLocked()) {
            return redirect()->route('erp.invoices.show', $invoice)
                ->with('error', 'This invoice is ' . $invoice->statusLabel() . ' and locked for editing.');
        }

        $invoice->load('items');

        return view('erp.invoices.edit', $this->formData($invoice->agency_id) + [
            'invoice'    => $invoice,
            'nextNumber' => $invoice->invoice_number,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorizeAgency($invoice);
        abort_if($invoice->isLocked(), 403, 'Locked invoice.');

        [$data, $items] = $this->validated($request, $invoice->agency_id);

        $this->invoices->save($invoice, $invoice->agency_id, $data, $items, auth()->user());

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} updated.");
    }

    /**
     * Soft delete. Tenant check first (another agency's invoice → 404, its
     * existence isn't revealed), then InvoicePolicy@delete (draft: admin or its
     * creator; pending/paid/cancelled: admin only). Reason + typed invoice
     * number are enforced here, not just by the modal's JS.
     */
    public function destroy(Request $request, Invoice $invoice)
    {
        abort_unless((int) $invoice->agency_id === (int) auth()->user()->agency_id, 404);
        $this->authorize('delete', $invoice);

        $request->merge([
            'delete_reason'  => trim((string) $request->input('delete_reason', '')),
            'confirm_number' => trim((string) $request->input('confirm_number', '')),
        ]);
        $validated = $request->validate([
            'delete_reason'  => ['required', 'string', 'min:5', 'max:1000'],
            'confirm_number' => ['required', 'string', Rule::in([$invoice->invoice_number])],
        ], [
            'confirm_number.in' => 'Type the invoice number exactly (' . $invoice->invoice_number . ') to confirm.',
        ], [
            'delete_reason'  => 'delete reason',
            'confirm_number' => 'invoice number',
        ]);

        $this->invoices->delete($invoice, auth()->user(), $validated['delete_reason']);

        return redirect()->route('erp.invoices.index')
            ->with('success', "Invoice {$invoice->invoice_number} deleted.");
    }

    public function markAsPaid(Request $request, Invoice $invoice)
    {
        $this->authorizeAgency($invoice);
        abort_unless($this->canReceivePayment(), 403);

        $validated = $request->validate([
            'payment_method'    => ['required', Rule::in(array_keys(Invoice::PAYMENT_METHODS))],
            'paid_at'           => ['required', 'date', 'before_or_equal:today'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $this->invoices->markPaid(
            $invoice,
            $validated['payment_method'],
            $validated['paid_at'],
            $validated['payment_reference'] ?? null,
            auth()->user(),
        );

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} marked as paid and locked.");
    }

    public function cancel(Invoice $invoice)
    {
        $this->authorizeAgency($invoice);
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $this->invoices->cancel($invoice, auth()->user());

        return redirect()->route('erp.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} cancelled.");
    }

    public function previewPdf(Invoice $invoice, PdfGeneratorService $pdf, BarcodeService $barcode)
    {
        return $this->pdf($invoice, $pdf, $barcode, true);
    }

    public function downloadPdf(Invoice $invoice, PdfGeneratorService $pdf, BarcodeService $barcode)
    {
        return $this->pdf($invoice, $pdf, $barcode, false);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function pdf(Invoice $invoice, PdfGeneratorService $pdf, BarcodeService $barcode, bool $inline)
    {
        $this->authorizeAgency($invoice);
        $invoice->load(['items.hrProfile.passport', 'agent']);

        return $pdf->generateFromView('prints.invoice', [
            'invoice'       => $invoice,
            'agency'        => auth()->user()->agency,
            'barcodeSrc'    => $barcode->make($invoice->invoice_number),
            'amountInWords' => NumberToWords::currency((string) $invoice->total_amount, $invoice->currency),
        ], $invoice->invoice_number, $inline);
    }

    /** Shared create/edit dropdown data — agency-scoped. */
    private function formData(int $agencyId): array
    {
        $passengers = $this->passengerOptions($agencyId);

        $agents = Agent::forAgency($agencyId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'address']);

        return [
            'passengers'     => $passengers,
            'agents'         => $agents,
            'statuses'       => array_intersect_key(Invoice::STATUSES, array_flip(Invoice::EDITABLE_STATUSES)),
            'currencies'     => Invoice::CURRENCIES,
            'adjustTypes'    => Invoice::ADJUST_TYPES,
        ];
    }

    /**
     * Passenger picker options: HR profiles plus passports that exist only in the
     * ERP modules (MOFA, Medical, Visa Stamping, BMET, Delivery, Double MOFA).
     * One option per passport; an HR profile wins over ERP rows. Agency-scoped.
     */
    private function passengerOptions(int $agencyId): \Illuminate\Support\Collection
    {
        $options = [];
        $norm = fn ($v) => strtoupper(trim((string) $v));

        HrProfile::forAgency($agencyId)
            ->with('passport:id,hr_profile_id,passport_number')
            ->orderBy('full_name_en')
            ->get(['id', 'full_name_en', 'file_number'])
            ->each(function (HrProfile $h) use (&$options, $norm) {
                $passport = $h->passport?->passport_number;
                $options[$passport ? 'P:' . $norm($passport) : 'H:' . $h->id] = [
                    'key'      => 'hr-' . $h->id,
                    'hr_id'    => $h->id,
                    'name'     => $h->full_name_en,
                    'passport' => $passport,
                    'file'     => $h->file_number,
                ];
            });

        // Newest ERP record first, so the latest typed name wins for a passport.
        $erp = [
            [\App\Models\MofaEntry::class, 'full_name'],
            [\App\Models\Medical::class, 'full_name'],
            [\App\Models\VisaStamping::class, 'full_name'],
            [\App\Models\BmetEntry::class, 'customer_name'],
            [\App\Models\Delivery::class, 'full_name'],
            [\App\Models\DoubleMofa::class, 'full_name'],
        ];
        foreach ($erp as [$model, $nameCol]) {
            $model::forAgency($agencyId)->whereNotNull('passport_no')->where('passport_no', '!=', '')
                ->orderByDesc('id')->get(['passport_no', $nameCol])
                ->each(function ($r) use (&$options, $norm, $nameCol) {
                    $passport = $norm($r->passport_no);
                    $options['P:' . $passport] ??= [
                        'key'      => 'erp-' . $passport,
                        'hr_id'    => null,
                        'name'     => $r->getAttributes()[$nameCol] ?? null,
                        'passport' => $passport,
                        'file'     => null,
                    ];
                });
        }

        return collect($options)->sortBy(fn ($o) => strtolower((string) $o['name']))->values();
    }

    /** @return array{0: array, 1: array} [header data, items] */
    private function validated(Request $request, int $agencyId): array
    {
        $validated = $request->validate([
            'invoice_date'    => ['required', 'date'],
            'due_date'        => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'agent_id'        => ['nullable', 'integer', Rule::exists('agents', 'id')->where('agency_id', $agencyId)],
            'bill_to_name'    => ['nullable', 'string', 'max:255'],
            'bill_to_phone'   => ['nullable', 'string', 'max:50'],
            'bill_to_address' => ['nullable', 'string', 'max:255'],
            'bill_to_email'   => ['nullable', 'email', 'max:255'],
            'status'          => ['required', Rule::in(Invoice::EDITABLE_STATUSES)],
            'currency'        => ['required', Rule::in(array_keys(Invoice::CURRENCIES))],

            'tax_type'        => ['required', Rule::in(array_keys(Invoice::ADJUST_TYPES))],
            'tax_value'       => ['exclude_if:tax_type,none', 'required', 'regex:' . self::MONEY],
            'discount_type'   => ['required', Rule::in(array_keys(Invoice::ADJUST_TYPES))],
            'discount_value'  => ['exclude_if:discount_type,none', 'required', 'regex:' . self::MONEY],
            'notes'           => ['nullable', 'string', 'max:2000'],

            'items'                    => ['required', 'array', 'min:1', 'max:100'],
            'items.*.hr_profile_id'    => ['nullable', 'integer', Rule::exists('hr_profiles', 'id')->where('agency_id', $agencyId)],
            'items.*.passenger_name'   => ['nullable', 'string', 'max:255'],
            'items.*.passport_no'      => ['nullable', 'string', 'max:100'],
            'items.*.processing_fee'   => ['required', 'regex:' . self::PRICE],
            'items.*.mofa_fee'         => ['required', 'regex:' . self::PRICE],
            'items.*.paid_amount'      => ['nullable', 'regex:' . self::PRICE],
            'items.*.remarks'          => ['nullable', 'string', 'max:500'],
        ], [
            'items.required'                  => 'Add at least one line item.',
            'items.*.processing_fee.required' => 'Processing fee is required (use 0 if none).',
            'items.*.processing_fee.regex'    => 'Processing fee must be a number with up to 2 decimals.',
            'items.*.mofa_fee.required'       => 'MOFA fee is required (use 0 if none).',
            'items.*.mofa_fee.regex'          => 'MOFA fee must be a number with up to 2 decimals.',
            'tax_value.regex'                 => 'Tax must be a number with up to 2 decimals.',
            'discount_value.regex'            => 'Discount must be a number with up to 2 decimals.',
        ], [
            'items.*.processing_fee' => 'processing fee',
            'items.*.mofa_fee'       => 'MOFA fee',
        ]);

        foreach (['tax', 'discount'] as $k) {
            if ($validated[$k . '_type'] === 'percent' && InvoiceService::toCents($validated[$k . '_value']) > 10000) {
                throw \Illuminate\Validation\ValidationException::withMessages([$k . '_value' => ucfirst($k) . ' percentage cannot exceed 100%.']);
            }
        }

        // HR-linked lines without a snapshot take the name/passport from the profile.
        $hr = HrProfile::forAgency($agencyId)->with('passport:id,hr_profile_id,passport_number')
            ->whereIn('id', array_filter(array_column($validated['items'], 'hr_profile_id')))
            ->get(['id', 'full_name_en'])->keyBy('id');
        $blank = fn ($v) => ($v = trim((string) $v)) === '' ? null : $v;

        $items = array_map(fn ($i) => [
            'hr_profile_id'  => $i['hr_profile_id'] ?? null,
            'passenger_name' => $blank($i['passenger_name'] ?? null) ?? $hr->get($i['hr_profile_id'] ?? 0)?->full_name_en,
            'passport_no'    => ($p = $blank($i['passport_no'] ?? null) ?? $hr->get($i['hr_profile_id'] ?? 0)?->passport?->passport_number) ? strtoupper($p) : null,
            'processing_fee' => $i['processing_fee'],
            'mofa_fee'       => $i['mofa_fee'],
            'paid_amount'    => $i['paid_amount'] ?? null,
            'remarks'        => $i['remarks'] ?? null,
        ], array_values($validated['items']));

        $data = collect($validated)->except('items')->all();

        return [$data, $items];
    }

    private function canReceivePayment(): bool
    {
        $user = auth()->user();

        return $user->isAgencyAdmin() || $user->can('erp_receive_payment');
    }

    private function authorizeAgency(Invoice $invoice): void
    {
        abort_unless((int) $invoice->agency_id === (int) auth()->user()->agency_id, 403);
    }

    private function isDate(string $v): bool
    {
        return $v !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false;
    }
}
