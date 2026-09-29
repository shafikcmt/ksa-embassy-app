<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentVoucherRequest;
use App\Models\AuditLog;
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
            'sort'   => array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'date_desc',
        ];

        $query = PaymentVoucher::forAgency($agencyId)->withCount('items');

        if ($filters['q'] !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $filters['q']) . '%';
            $query->where(fn ($w) => $w->where('voucher_number', 'like', $like)
                ->orWhere('payee_name', 'like', $like)
                ->orWhere('payee_phone', 'like', $like)
                ->orWhere('description', 'like', $like)
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

        return view('erp.payment-vouchers.index', [
            'vouchers' => $query->paginate(12)->withQueryString(),
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
        $paymentVoucher->load(['items', 'expense', 'createdBy:id,name', 'approvedBy:id,name', 'paidBy:id,name']);

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

        return view('erp.payment-vouchers.edit', $this->formData() + [
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

    public function previewPdf(PaymentVoucher $paymentVoucher, PdfGeneratorService $pdf)
    {
        return $this->pdf($paymentVoucher, $pdf, true);
    }

    public function downloadPdf(PaymentVoucher $paymentVoucher, PdfGeneratorService $pdf)
    {
        return $this->pdf($paymentVoucher, $pdf, false);
    }

    private function pdf(PaymentVoucher $voucher, PdfGeneratorService $pdf, bool $inline)
    {
        $this->authorize('view', $voucher);
        $voucher->load(['items', 'createdBy:id,name', 'approvedBy:id,name', 'paidBy:id,name']);

        return $pdf->generateFromView('prints.payment-voucher', [
            'voucher'       => $voucher,
            'agency'        => auth()->user()->agency,
            'amountInWords' => NumberToWords::currency((string) $voucher->total_amount, 'BDT'),
        ], 'Payment-Voucher-' . $voucher->voucher_number, $inline);
    }

    private function formData(): array
    {
        return [
            'payeeTypes'     => PaymentVoucher::PAYEE_TYPES,
            'paymentMethods' => PaymentVoucher::PAYMENT_METHODS,
            'adjustTypes'    => PaymentVoucher::ADJUST_TYPES,
        ];
    }
}
