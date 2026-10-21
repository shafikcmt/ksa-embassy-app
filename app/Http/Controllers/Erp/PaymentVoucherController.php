<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentVoucherRequest;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\ExpenseHead;
use App\Models\PaymentVoucher;
use App\Services\PaymentVoucherService;
use App\Services\PdfGeneratorService;
use App\Support\NumberToWords;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * ERP Payment Vouchers — agency money-OUT documents.
 *
 * Tenancy + roles: App\Policies\PaymentVoucherPolicy (every action authorizes).
 * Money, numbering and lifecycle writes: App\Services\PaymentVoucherService only.
 * Workflow: draft → approved → paid; cancel from draft/approved; delete (soft)
 * drafts only.
 */
class PaymentVoucherController extends Controller
{
    /** Vouchers per "Print selected" run. */
    private const BATCH_LIMIT = 50;

    /** Extra rows (see rowWeight) a voucher may carry and still share an A4 sheet with another one (2 is the hard limit with unchanged fonts; 1 leaves a row of slack). */
    public const HALF_PAGE_MAX_ROWS = 1;

    /** Voucher sheets use a slim top margin (the header starts right under it). */
    private const PRINT_OPTIONS = ['margin_top' => 6, 'margin_bottom' => 12];

    public const SORTS = [
        'date_desc'   => 'Newest first',
        'date_asc'    => 'Oldest first',
        'amount_desc' => 'Amount: high → low',
        'amount_asc'  => 'Amount: low → high',
        'number_desc' => 'Voucher no.',
    ];

    public function __construct(private PaymentVoucherService $service)
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
            'month'  => '',
            'sort'   => array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'date_desc',
        ];
        // A chosen month wins over From/To: the whole calendar month of the voucher date.
        if ($bounds = \App\Support\MonthRange::bounds((string) $request->query('month', ''))) {
            [$filters['month'], $filters['from'], $filters['to']] = [(string) $request->query('month'), $bounds[0], $bounds[1]];
        }

        $query = PaymentVoucher::forAgency($agencyId)->with('expenseHead');

        if ($filters['q'] !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $filters['q']) . '%';
            $query->where(fn ($w) => $w->where('voucher_number', 'like', $like)
                ->orWhere('payee_name', 'like', $like)
                ->orWhere('payee_phone', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('expenseHead', fn ($h) => $h->where('agency_id', $agencyId)->where('name', 'like', $like))
                ->orWhereHas('items', fn ($i) => $i->where('description', 'like', $like)));
        }
        if (array_key_exists($filters['status'], PaymentVoucher::STATUSES)) {
            $query->where('status', $filters['status']);
        }
        foreach (['from' => '>=', 'to' => '<='] as $key => $op) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$key])) {
                $query->whereDate('voucher_date', $op, $filters[$key]);
            }
        }

        match ($filters['sort']) {
            'date_asc'    => $query->orderBy('voucher_date')->orderBy('id'),
            'amount_desc' => $query->orderByDesc('total_amount')->orderByDesc('id'),
            'amount_asc'  => $query->orderBy('total_amount')->orderBy('id'),
            'number_desc' => $query->orderByDesc('number_year')->orderByDesc('number_seq'),
            default       => $query->orderByDesc('voucher_date')->orderByDesc('id'),
        };

        $summary = PaymentVoucher::forAgency($agencyId)
            ->selectRaw('status, COUNT(*) as cnt, SUM(total_amount) as total')
            ->groupBy('status')->get()->keyBy('status');

        // "Print all" for a filtered month: the ids of every match (capped at the print limit + 1 to detect overflow).
        $printIds = $filters['month'] !== '' ? (clone $query)->limit(self::BATCH_LIMIT + 1)->pluck('id')->all() : [];

        return view('erp.payment-vouchers.index', [
            'vouchers' => $query->paginate(15)->withQueryString(),
            'printIds' => $printIds,
            'filters'  => $filters,
            'sorts'    => self::SORTS,
            'statuses' => PaymentVoucher::STATUSES,
            'summary'  => $summary,
        ]);
    }

    public function create()
    {
        $this->authorize('create', PaymentVoucher::class);
        $agencyId = auth()->user()->agency_id;

        return view('erp.payment-vouchers.create', $this->formData() + [
            'voucher'    => new PaymentVoucher([
                'voucher_date'   => today(),
                'payee_type'     => 'party',
                'payment_method' => 'cash',
                'tax_type'       => 'none',
                'discount_type'  => 'none',
            ]),
            'nextNumber' => $this->service->peekNextNumber($agencyId, (int) now()->year),
        ]);
    }

    public function store(PaymentVoucherRequest $request)
    {
        $this->authorize('create', PaymentVoucher::class);
        [$data, $items] = $request->voucherData();

        $voucher = $this->service->save(null, auth()->user()->agency_id, $data, $items, auth()->user());

        return redirect()->route('erp.payment-vouchers.show', $voucher)
            ->with('success', "Payment voucher {$voucher->voucher_number} created as draft.");
    }

    public function show(PaymentVoucher $paymentVoucher)
    {
        $this->authorize('view', $paymentVoucher);
        $paymentVoucher->load(['items', 'expense', 'expenseHead', 'createdBy:id,name', 'approvedBy:id,name', 'paidBy:id,name']);

        $timeline = AuditLog::with('user:id,name')
            ->where('agency_id', $paymentVoucher->agency_id)
            ->where('auditable_type', PaymentVoucher::class)
            ->where('auditable_id', $paymentVoucher->id)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit(30)->get();

        return view('erp.payment-vouchers.show', [
            'voucher'        => $paymentVoucher,
            'timeline'       => $timeline,
            'paymentMethods' => PaymentVoucher::PAYMENT_METHODS,
        ]);
    }

    public function edit(PaymentVoucher $paymentVoucher)
    {
        $this->authorize('view', $paymentVoucher);
        if (! $paymentVoucher->isEditable()) {
            return redirect()->route('erp.payment-vouchers.show', $paymentVoucher)
                ->with('error', 'This voucher is ' . $paymentVoucher->statusLabel() . ' — only drafts can be edited.');
        }
        $this->authorize('update', $paymentVoucher);

        return view('erp.payment-vouchers.edit', $this->formData($paymentVoucher) + [
            'voucher'    => $paymentVoucher->load('items'),
            'nextNumber' => $paymentVoucher->voucher_number,
        ]);
    }

    public function update(PaymentVoucherRequest $request, PaymentVoucher $paymentVoucher)
    {
        $this->authorize('update', $paymentVoucher);
        [$data, $items] = $request->voucherData();

        $this->service->save($paymentVoucher, $paymentVoucher->agency_id, $data, $items, auth()->user());

        return redirect()->route('erp.payment-vouchers.show', $paymentVoucher)
            ->with('success', "Payment voucher {$paymentVoucher->voucher_number} updated.");
    }

    public function destroy(PaymentVoucher $paymentVoucher)
    {
        $this->authorize('delete', $paymentVoucher);
        $this->service->delete($paymentVoucher, auth()->user());

        return redirect()->route('erp.payment-vouchers.index')
            ->with('success', "Draft voucher {$paymentVoucher->voucher_number} deleted.");
    }

    public function approve(PaymentVoucher $paymentVoucher)
    {
        $this->authorize('approve', $paymentVoucher);
        $this->service->approve($paymentVoucher, auth()->user());

        return redirect()->route('erp.payment-vouchers.show', $paymentVoucher)
            ->with('success', "Payment voucher {$paymentVoucher->voucher_number} approved.");
    }

    public function markPaid(Request $request, PaymentVoucher $paymentVoucher)
    {
        $this->authorize('pay', $paymentVoucher);

        $validated = $request->validate([
            'payment_method'   => ['required', Rule::in(array_keys(PaymentVoucher::PAYMENT_METHODS))],
            'payment_date'     => ['required', 'date', 'before_or_equal:today'],
            'cheque_number'    => ['nullable', 'required_if:payment_method,cheque', 'string', 'max:50'],
            'bank_name'        => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ], ['cheque_number.required_if' => 'Cheque number is required for cheque payments.']);

        $this->service->pay($paymentVoucher, auth()->user(), $validated);

        return redirect()->route('erp.payment-vouchers.show', $paymentVoucher)
            ->with('success', "Payment voucher {$paymentVoucher->voucher_number} marked as paid and booked in Expenses.");
    }

    public function cancel(PaymentVoucher $paymentVoucher)
    {
        $this->authorize('cancel', $paymentVoucher);
        $this->service->cancel($paymentVoucher, auth()->user());

        return redirect()->route('erp.payment-vouchers.show', $paymentVoucher)
            ->with('success', "Payment voucher {$paymentVoucher->voucher_number} cancelled.");
    }

    /** Print button → print-dialog wrapper; ?raw=1 → the inline PDF it embeds. */
    public function previewPdf(PaymentVoucher $paymentVoucher, PdfGeneratorService $pdf)
    {
        if (request()->boolean('raw')) {
            return $this->pdf($paymentVoucher, $pdf, true);
        }
        $this->authorize('view', $paymentVoucher);

        $position = $this->position();
        $half = $this->isHalfSize($paymentVoucher);

        return view('prints.pdf-print', [
            'title'       => 'Payment Voucher ' . $paymentVoucher->voucher_number,
            'pdfUrl'      => route('erp.payment-vouchers.preview-pdf', [$paymentVoucher, 'raw' => 1, 'position' => $position]),
            'downloadUrl' => route('erp.payment-vouchers.download-pdf', [$paymentVoucher, 'position' => $position]),
            'backUrl'     => route('erp.payment-vouchers.show', $paymentVoucher),
            // Half-sheet vouchers can go on the top or the bottom half, so one sheet carries two prints.
            'positions'   => $half ? ['current' => $position, 'top' => route('erp.payment-vouchers.preview-pdf', [$paymentVoucher, 'position' => 'top']), 'bottom' => route('erp.payment-vouchers.preview-pdf', [$paymentVoucher, 'position' => 'bottom'])] : null,
        ]);
    }

    public function downloadPdf(PaymentVoucher $paymentVoucher, PdfGeneratorService $pdf)
    {
        return $this->pdf($paymentVoucher, $pdf, false);
    }

    /**
     * Print several vouchers picked on the list: ?ids=3,5,8 (max BATCH_LIMIT).
     * Two normal vouchers share one A4 page; heavy ones get a page of their own.
     * Default → print-dialog wrapper; ?raw=1 → inline PDF; ?download=1 → attachment.
     * Ids outside the user's agency (or deleted) are dropped silently — never exposed.
     */
    public function printSelected(Request $request, PdfGeneratorService $pdf)
    {
        $raw = (string) $request->query('ids', '');
        abort_unless(preg_match('/^\d+(,\d+)*$/', $raw) === 1, 422, 'Select at least one voucher.');
        $ids = array_values(array_unique(array_map('intval', explode(',', $raw))));
        abort_if(count($ids) > self::BATCH_LIMIT, 422, 'You can print up to ' . self::BATCH_LIMIT . ' vouchers at once.');

        $vouchers = PaymentVoucher::forAgency((int) auth()->user()->agency_id)
            ->whereIn('id', $ids)
            ->with(['items', 'expenseHead', 'createdBy:id,name', 'approvedBy:id,name', 'paidBy:id,name'])
            ->get()
            ->sortBy(fn (PaymentVoucher $v) => array_search($v->id, $ids, true))
            ->values();
        abort_if($vouchers->isEmpty(), 404);

        $query = ['ids' => implode(',', $vouchers->pluck('id')->all())];
        $position = $this->position();
        $lone = $vouchers->count() === 1 && $this->isHalfSize($vouchers->first());
        $posQuery = $lone ? ['position' => $position] : [];

        if (! $request->boolean('raw') && ! $request->boolean('download')) {
            return view('prints.pdf-print', [
                'title'       => 'Print ' . $vouchers->count() . ' ' . \Illuminate\Support\Str::plural('voucher', $vouchers->count()),
                'pdfUrl'      => route('erp.payment-vouchers.print-selected', $query + $posQuery + ['raw' => 1]),
                'downloadUrl' => route('erp.payment-vouchers.print-selected', $query + $posQuery + ['download' => 1]),
                'backUrl'     => route('erp.payment-vouchers.index'),
                'positions'   => $lone ? ['current' => $position, 'top' => route('erp.payment-vouchers.print-selected', $query + ['position' => 'top']), 'bottom' => route('erp.payment-vouchers.print-selected', $query + ['position' => 'bottom'])] : null,
            ]);
        }

        $sheets = $vouchers->map(fn (PaymentVoucher $v) => [
            'voucher'       => $v,
            'amountInWords' => NumberToWords::currency((string) $v->total_amount, 'BDT'),
            'half'          => $this->rowWeight($v) <= self::HALF_PAGE_MAX_ROWS,
        ])->all();

        return $pdf->generateFromView('prints.payment-voucher-batch', [
            'sheets'   => $sheets,
            'agency'   => auth()->user()->agency,
            'position' => $position,
        ], 'payment-vouchers-' . now()->format('Ymd-His'), $request->boolean('raw'), self::PRINT_OPTIONS);
    }

    /** Extra vertical rows a voucher needs: remarks lines, historic tax/discount, payee address/phone. */
    private function rowWeight(PaymentVoucher $v): int
    {
        $lines = fn (?string $text) => ($t = trim((string) $text)) === '' ? 0
            : max((int) ceil(mb_strlen($t) / 80), substr_count($t, "\n") + 1);
        $description = $v->description && $v->description !== $v->expenseHeadLabel() ? $v->description : '';

        return $lines($description) + $lines($v->notes)
            + ((float) $v->tax_amount > 0 ? 1 : 0) + ((float) $v->discount_amount > 0 ? 1 : 0)
            + ($v->payee_address ? 1 : 0) + ($v->payee_phone ? 1 : 0) + ($v->payee_account ? 1 : 0);
    }

    private function pdf(PaymentVoucher $voucher, PdfGeneratorService $pdf, bool $inline)
    {
        $this->authorize('view', $voucher);
        $voucher->load(['items', 'expenseHead', 'createdBy:id,name', 'approvedBy:id,name', 'paidBy:id,name']);
        $words = NumberToWords::currency((string) $voucher->total_amount, 'BDT');

        // A normal voucher prints on HALF an A4 sheet (top by default, ?position=bottom for the lower half),
        // so the same sheet can go back into the printer for the next one. Heavy vouchers keep the full page.
        if ($this->isHalfSize($voucher)) {
            return $pdf->generateFromView('prints.payment-voucher-batch', [
                'sheets'   => [['voucher' => $voucher, 'amountInWords' => $words, 'half' => true]],
                'agency'   => auth()->user()->agency,
                'position' => $this->position(),
            ], 'Payment-Voucher-' . $voucher->voucher_number, $inline, self::PRINT_OPTIONS);
        }

        return $pdf->generateFromView('prints.payment-voucher', [
            'voucher'       => $voucher,
            'agency'        => auth()->user()->agency,
            'amountInWords' => $words,
        ], 'Payment-Voucher-' . $voucher->voucher_number, $inline, self::PRINT_OPTIONS);
    }

    private function isHalfSize(PaymentVoucher $voucher): bool
    {
        return $this->rowWeight($voucher->loadMissing('items', 'expenseHead')) <= self::HALF_PAGE_MAX_ROWS;
    }

    /** Which half of the sheet a lone voucher prints on: top (default) or bottom. */
    private function position(): string
    {
        return request()->query('position') === 'bottom' ? 'bottom' : 'top';
    }

    private function formData(?PaymentVoucher $voucher = null): array
    {
        return [
            'expenseHeads' => ExpenseHead::forAgency(auth()->user()->agency_id)
                ->where(fn ($q) => $q->where('is_active', true)
                    ->when($voucher?->expense_head_id, fn ($same) => $same->orWhere('id', $voucher->expense_head_id)))
                ->ordered()->get(),
            'paymentMethods' => PaymentVoucher::PAYMENT_METHODS,
            // "Paid To" suggestions: names used on earlier vouchers + this agency's agents (never typed twice).
            'payeeSuggestions' => PaymentVoucher::where('agency_id', (int) auth()->user()->agency_id)
                ->whereNotNull('payee_name')->where('payee_name', '!=', '')
                ->distinct()->orderBy('payee_name')->limit(200)->pluck('payee_name')
                ->merge(Agent::forAgency((int) auth()->user()->agency_id)->orderBy('name')->pluck('name'))
                ->unique()->values(),
        ];
    }
}
