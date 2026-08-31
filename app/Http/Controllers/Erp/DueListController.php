<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DoubleMofa;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * ERP Due List (E3, sub-phase 2). READ-ONLY cross-module report.
 *
 * Reads ONLY the existing E2 money tables (deliveries, double_mofas) and lists
 * rows whose outstanding balance (billed − paid) is > 0. There is NO new money
 * table and NO money math here: `paid_amount` is the E2 ledger-backed cache, so
 * this view inherits E2's verified correctness. Any payment taken from this page
 * POSTs to the EXISTING erp.delivery.payment / erp.double-mofa.payment endpoints
 * (i.e. ErpPaymentService) — this controller never moves money itself.
 */
class DueListController extends Controller
{
    public function index(Request $request)
    {
        $agencyId = auth()->user()->agency_id;

        $type = in_array($request->query('type'), ['delivery', 'double_mofa'], true)
            ? $request->query('type') : 'all';
        $from = $request->query('from');
        $to   = $request->query('to');
        $q    = trim((string) $request->query('q', ''));
        $sort = in_array($request->query('sort'), ['due', 'date', 'name'], true)
            ? $request->query('sort') : 'due';

        $rows = collect();

        if ($type === 'all' || $type === 'delivery') {
            $rows = $rows->concat($this->deliveryRows($agencyId, $from, $to, $q));
        }
        if ($type === 'all' || $type === 'double_mofa') {
            $rows = $rows->concat($this->doubleMofaRows($agencyId, $from, $to, $q));
        }

        $rows = (match ($sort) {
            'date'  => $rows->sortByDesc('date_sort'),
            'name'  => $rows->sortBy('full_name'),
            default => $rows->sortByDesc('due'),
        })->values();

        // Summary reflects the CURRENT filtered view.
        $deliveryDue   = (float) $rows->where('source', 'delivery')->sum('due');
        $doubleMofaDue = (float) $rows->where('source', 'double_mofa')->sum('due');

        return view('erp.due-list.index', [
            'rows'          => $rows,
            'deliveryDue'   => $deliveryDue,
            'doubleMofaDue' => $doubleMofaDue,
            'combinedDue'   => $deliveryDue + $doubleMofaDue,
            'count'         => $rows->count(),
            'filters'       => compact('type', 'from', 'to', 'q', 'sort'),
        ]);
    }

    private function deliveryRows(int $agencyId, $from, $to, string $q): Collection
    {
        $query = Delivery::forAgency($agencyId)
            ->whereRaw('total_amount - paid_amount > 0');

        if ($from) $query->whereDate('delivery_date', '>=', $from);
        if ($to)   $query->whereDate('delivery_date', '<=', $to);
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('full_name', 'like', "%{$q}%")
                ->orWhere('passport_no', 'like', "%{$q}%"));
        }

        return $query->get()->map(fn (Delivery $d) => [
            'source'       => 'delivery',
            'source_label' => 'Delivery',
            'id'           => $d->id,
            'date'         => $d->delivery_date->format('d M Y'),
            'date_sort'    => $d->delivery_date->format('Y-m-d'),
            'full_name'    => $d->full_name,
            'passport_no'  => $d->passport_no,
            'billed'       => (float) $d->total_amount,
            'paid'         => (float) $d->paid_amount,
            'due'          => (float) $d->total_amount - (float) $d->paid_amount,
            'status'       => $d->statusLabel(),
            'pay_url'      => route('erp.delivery.payment', $d),
        ]);
    }

    private function doubleMofaRows(int $agencyId, $from, $to, string $q): Collection
    {
        $query = DoubleMofa::forAgency($agencyId)
            ->whereRaw('billing_amount - paid_amount > 0');

        if ($from) $query->whereDate('mofa_date', '>=', $from);
        if ($to)   $query->whereDate('mofa_date', '<=', $to);
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('full_name', 'like', "%{$q}%")
                ->orWhere('passport_no', 'like', "%{$q}%"));
        }

        return $query->get()->map(fn (DoubleMofa $m) => [
            'source'       => 'double_mofa',
            'source_label' => 'Double MOFA',
            'id'           => $m->id,
            'date'         => $m->mofa_date->format('d M Y'),
            'date_sort'    => $m->mofa_date->format('Y-m-d'),
            'full_name'    => $m->full_name,
            'passport_no'  => $m->passport_no,
            'billed'       => (float) $m->billing_amount,
            'paid'         => (float) $m->paid_amount,
            'due'          => (float) $m->billing_amount - (float) $m->paid_amount,
            'status'       => $m->statusLabel(),
            'pay_url'      => route('erp.double-mofa.payment', $m),
        ]);
    }
}
