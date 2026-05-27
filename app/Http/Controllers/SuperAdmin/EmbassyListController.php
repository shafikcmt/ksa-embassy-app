<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\EmbassyList;
use Illuminate\Http\Request;

class EmbassyListController extends Controller
{
    public function index(Request $request)
    {
        $query = EmbassyList::with(['agency'])->withCount('items')->latest('list_date');

        if ($agencyId = $request->input('agency_id')) {
            $query->where('agency_id', $agencyId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->input('date_from')) {
            $query->where('list_date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->where('list_date', '<=', $to);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('list_no', 'like', "%{$search}%")
                  ->orWhereHas('agency', fn($qa) => $qa->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('items', fn($qi) => $qi
                      ->where('snapshot_candidate_name', 'like', "%{$search}%")
                      ->orWhere('snapshot_passport_no', 'like', "%{$search}%")
                  );
            });
        }

        $embassyLists = $query->paginate(20)->withQueryString();
        $agencies     = Agency::orderBy('name')->get(['id', 'name']);

        return view('super-admin.embassy-lists.index', compact('embassyLists', 'agencies'));
    }

    public function show(EmbassyList $embassyList)
    {
        $embassyList->load(['agency', 'createdBy', 'updatedBy']);

        $itemsByCategory = $embassyList->items()
            ->orderBy('category')
            ->orderBy('serial_no')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('category');

        return view('super-admin.embassy-lists.show', compact('embassyList', 'itemsByCategory'));
    }
}
