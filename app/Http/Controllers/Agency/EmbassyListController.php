<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmbassyListRequest;
use App\Http\Requests\UpdateEmbassyListRequest;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\EmbassyList;
use App\Models\EmbassyListItem;
use App\Models\HrProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmbassyListController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', EmbassyList::class);

        $agencyId = auth()->user()->agency_id;

        $query = EmbassyList::withCount('items')
            ->forAgency($agencyId)
            ->latest('list_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('list_no', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('items', fn($qi) => $qi
                      ->where('snapshot_candidate_name', 'like', "%{$search}%")
                      ->orWhere('snapshot_passport_no', 'like', "%{$search}%")
                      ->orWhere('snapshot_visa_no', 'like', "%{$search}%")
                  );
            });
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

        $embassyLists = $query->paginate(15)->withQueryString();

        $subscription   = auth()->user()->agency?->activeSubscription;
        $monthlyLimit   = $subscription?->plan?->max_embassy_lists_monthly ?? 0;
        $monthlyCount   = EmbassyList::forAgency($agencyId)->thisMonth()
            ->where('status', '!=', 'cancelled')->count();
        $totalCount     = EmbassyList::forAgency($agencyId)->count();
        $draftCount     = EmbassyList::forAgency($agencyId)->draft()->count();
        $finalizedCount = EmbassyList::forAgency($agencyId)->finalized()->count();

        return view('agency.embassy-lists.index', compact(
            'embassyLists', 'monthlyLimit', 'monthlyCount',
            'totalCount', 'draftCount', 'finalizedCount'
        ));
    }

    public function create()
    {
        $this->authorize('create', EmbassyList::class);
        $this->enforcePlanLimit();

        $agencyId      = auth()->user()->agency_id;
        $agents        = Agent::forAgency($agencyId)->active()->orderBy('name')->get();
        $availableHr   = $this->loadAvailableHr($agencyId);

        return view('agency.embassy-lists.create', compact('agents', 'availableHr'));
    }

    public function store(StoreEmbassyListRequest $request)
    {
        $this->authorize('create', EmbassyList::class);
        $this->enforcePlanLimit();

        $user     = auth()->user();
        $agencyId = $user->agency_id;

        // Validate all HR IDs belong to this agency
        $submittedIds = collect($request->items)->pluck('hr_profile_id')->unique();
        $validIds = HrProfile::where('agency_id', $agencyId)
            ->whereIn('id', $submittedIds)
            ->pluck('id');

        if ($validIds->count() !== $submittedIds->count()) {
            abort(422, 'One or more selected candidates do not belong to your agency.');
        }

        // Load HR profiles with relations for snapshotting
        $hrMap = HrProfile::with(['passport', 'visa', 'agent'])
            ->whereIn('id', $validIds)
            ->get()
            ->keyBy('id');

        $embassyList = DB::transaction(function () use ($request, $user, $agencyId, $hrMap) {
            $embassyList = EmbassyList::create([
                'agency_id'  => $agencyId,
                'list_no'    => $this->generateListNo($agencyId),
                'title'      => $request->title,
                'list_date'  => $request->list_date,
                'status'     => 'draft',
                'notes'      => $request->notes,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($request->items as $sortOrder => $item) {
                $hr = $hrMap[$item['hr_profile_id']];
                EmbassyListItem::create(
                    $this->buildItemData($embassyList, $hr, $item['category'], $sortOrder)
                );
            }

            $embassyList->recalculateTotals();
            return $embassyList;
        });

        AuditLog::record('create', $embassyList, [], ['list_no' => $embassyList->list_no, 'total_items' => $embassyList->total_items]);

        return redirect()->route('embassy-lists.show', $embassyList)
            ->with('success', "Embassy list {$embassyList->list_no} created successfully.");
    }

    public function show(EmbassyList $embassyList)
    {
        $this->authorize('view', $embassyList);

        $embassyList->load(['agency', 'createdBy', 'updatedBy']);

        $itemsByCategory = $embassyList->items()
            ->orderBy('category')
            ->orderBy('serial_no')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('category');

        return view('agency.embassy-lists.show', compact('embassyList', 'itemsByCategory'));
    }

    public function edit(EmbassyList $embassyList)
    {
        $this->authorize('update', $embassyList);

        if (! $embassyList->canEdit()) {
            return redirect()->route('embassy-lists.show', $embassyList)
                ->with('error', 'Only draft lists can be edited.');
        }

        $agencyId    = auth()->user()->agency_id;
        $agents      = Agent::forAgency($agencyId)->active()->orderBy('name')->get();
        $availableHr = $this->loadAvailableHr($agencyId, $embassyList->id);

        // Build map of currently selected items: hr_profile_id => category
        $selectedItems = $embassyList->items()
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->mapWithKeys(fn($item) => [$item->hr_profile_id => $item->category]);

        return view('agency.embassy-lists.edit', compact('embassyList', 'agents', 'availableHr', 'selectedItems'));
    }

    public function update(UpdateEmbassyListRequest $request, EmbassyList $embassyList)
    {
        $this->authorize('update', $embassyList);

        if (! $embassyList->canEdit()) {
            abort(403, 'Only draft lists can be edited.');
        }

        $user     = auth()->user();
        $agencyId = $user->agency_id;

        $submittedIds = collect($request->items)->pluck('hr_profile_id')->unique();
        $validIds = HrProfile::where('agency_id', $agencyId)
            ->whereIn('id', $submittedIds)
            ->pluck('id');

        if ($validIds->count() !== $submittedIds->count()) {
            abort(422, 'One or more selected candidates do not belong to your agency.');
        }

        $hrMap = HrProfile::with(['passport', 'visa', 'agent'])
            ->whereIn('id', $validIds)
            ->get()
            ->keyBy('id');

        $oldValues = ['list_no' => $embassyList->list_no, 'total_items' => $embassyList->total_items];

        DB::transaction(function () use ($request, $user, $agencyId, $embassyList, $hrMap) {
            $embassyList->update([
                'title'      => $request->title,
                'list_date'  => $request->list_date,
                'notes'      => $request->notes,
                'updated_by' => $user->id,
            ]);

            // Replace all items
            $embassyList->items()->delete();

            foreach ($request->items as $sortOrder => $item) {
                $hr = $hrMap[$item['hr_profile_id']];
                EmbassyListItem::create(
                    $this->buildItemData($embassyList, $hr, $item['category'], $sortOrder)
                );
            }

            $embassyList->recalculateTotals();
        });

        AuditLog::record('update', $embassyList, $oldValues, ['total_items' => $embassyList->fresh()->total_items]);

        return redirect()->route('embassy-lists.show', $embassyList)
            ->with('success', 'Embassy list updated successfully.');
    }

    public function destroy(EmbassyList $embassyList)
    {
        $this->authorize('delete', $embassyList);

        if ($embassyList->isFinalized()) {
            return redirect()->route('embassy-lists.show', $embassyList)
                ->with('error', 'Finalized lists cannot be deleted. Cancel the list first.');
        }

        AuditLog::record('delete', $embassyList, ['list_no' => $embassyList->list_no], []);
        $embassyList->delete();

        return redirect()->route('embassy-lists.index')
            ->with('success', "Embassy list {$embassyList->list_no} deleted.");
    }

    public function finalize(EmbassyList $embassyList)
    {
        $this->authorize('finalize', $embassyList);

        if (! $embassyList->isDraft()) {
            return redirect()->route('embassy-lists.show', $embassyList)
                ->with('error', 'Only draft lists can be finalized.');
        }

        if ($embassyList->items()->count() === 0) {
            return redirect()->route('embassy-lists.show', $embassyList)
                ->with('error', 'Cannot finalize an empty list. Add at least one candidate.');
        }

        DB::transaction(function () use ($embassyList) {
            // Assign serial numbers per category
            foreach (['new', 'restamping', 'cancellation'] as $category) {
                $serial = 1;
                $embassyList->items()
                    ->where('category', $category)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->each(function ($item) use (&$serial) {
                        $item->update(['serial_no' => $serial++]);
                    });
            }

            $embassyList->update([
                'status'       => 'finalized',
                'finalized_at' => now(),
                'updated_by'   => auth()->id(),
            ]);

            $embassyList->recalculateTotals();

            // Mark HR profiles as listed
            $hrIds = $embassyList->items()->pluck('hr_profile_id');
            HrProfile::whereIn('id', $hrIds)->update(['status' => 'listed']);
        });

        AuditLog::record('finalize', $embassyList, [], [
            'list_no'     => $embassyList->list_no,
            'total_items' => $embassyList->total_items,
            'finalized_at'=> now()->toDateTimeString(),
        ]);

        return redirect()->route('embassy-lists.show', $embassyList)
            ->with('success', "List {$embassyList->list_no} finalized. {$embassyList->total_items} candidate(s) marked as listed.");
    }

    public function cancel(EmbassyList $embassyList)
    {
        $this->authorize('cancel', $embassyList);

        if ($embassyList->isCancelled()) {
            return redirect()->route('embassy-lists.show', $embassyList)
                ->with('error', 'List is already cancelled.');
        }

        $wasFinalized = $embassyList->isFinalized();

        DB::transaction(function () use ($embassyList, $wasFinalized) {
            $embassyList->update([
                'status'     => 'cancelled',
                'updated_by' => auth()->id(),
            ]);

            // If it was finalized, reset HR profiles that aren't in another finalized list
            if ($wasFinalized) {
                $hrIds = $embassyList->items()->pluck('hr_profile_id');
                foreach ($hrIds as $hrId) {
                    $inAnotherFinalizedList = EmbassyListItem::whereHas('embassyList', function ($q) use ($embassyList) {
                        $q->whereIn('status', ['finalized', 'printed'])
                          ->where('id', '!=', $embassyList->id);
                    })->where('hr_profile_id', $hrId)->exists();

                    if (! $inAnotherFinalizedList) {
                        HrProfile::where('id', $hrId)->update(['status' => 'active']);
                    }
                }
            }
        });

        AuditLog::record('cancel', $embassyList, ['status' => $wasFinalized ? 'finalized' : 'draft'], ['status' => 'cancelled']);

        return redirect()->route('embassy-lists.show', $embassyList)
            ->with('success', "List {$embassyList->list_no} has been cancelled.");
    }

    public function print(EmbassyList $embassyList)
    {
        $this->authorize('view', $embassyList);

        $embassyList->load('agency');

        // Group: restamping first, then new, then cancellation (standard embassy order)
        $itemsByCategory = $embassyList->items()
            ->orderByRaw("FIELD(category, 'restamping', 'new', 'cancellation')")
            ->orderBy('serial_no')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('category');

        // Mark as printed if finalized
        if ($embassyList->isFinalized() && ! $embassyList->printed_at) {
            $embassyList->update(['status' => 'printed', 'printed_at' => now()]);
            AuditLog::record('print', $embassyList, [], ['printed_at' => now()->toDateTimeString()]);
        }

        return view('agency.embassy-lists.print', compact('embassyList', 'itemsByCategory'));
    }

    public function availableHr(Request $request)
    {
        $agencyId     = auth()->user()->agency_id;
        $excludeListId = $request->integer('exclude_list');

        $query = HrProfile::with(['agent:id,name', 'passport:id,hr_profile_id,passport_number', 'visa:id,hr_profile_id,visa_number,sponsor_name,sponsor_id'])
            ->forAgency($agencyId)
            ->whereIn('status', ['active', 'inactive', 'listed'])
            ->orderBy('full_name_en');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name_en', 'like', "%{$search}%")
                  ->orWhere('full_name_ar', 'like', "%{$search}%")
                  ->orWhere('nationality', 'like', "%{$search}%")
                  ->orWhereHas('passport', fn($p) => $p->where('passport_number', 'like', "%{$search}%"));
            });
        }

        if ($agentId = $request->input('agent_id')) {
            $query->where('agent_id', $agentId);
        }

        $hrProfiles = $query->limit(200)->get();

        // Get IDs already in finalized/draft lists (for warning indicators)
        $listedInDraft = EmbassyListItem::whereHas('embassyList', function ($q) use ($agencyId, $excludeListId) {
            $q->where('agency_id', $agencyId)
              ->whereIn('status', ['draft', 'finalized'])
              ->when($excludeListId, fn($q) => $q->where('id', '!=', $excludeListId));
        })->pluck('hr_profile_id')->unique();

        return response()->json([
            'hr' => $hrProfiles->map(fn($hr) => [
                'id'             => $hr->id,
                'full_name_en'   => $hr->full_name_en,
                'full_name_ar'   => $hr->full_name_ar,
                'nationality'    => $hr->nationality,
                'occupation'     => $hr->occupation,
                'status'         => $hr->status,
                'agent_id'       => $hr->agent_id,
                'agent_name'     => $hr->agent?->name,
                'passport_no'    => $hr->passport?->passport_number,
                'visa_no'        => $hr->visa?->visa_number,
                'sponsor_name'   => $hr->visa?->sponsor_name,
                'sponsor_id'     => $hr->visa?->sponsor_id,
                'in_active_list' => $listedInDraft->contains($hr->id),
            ]),
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function buildItemData(EmbassyList $list, HrProfile $hr, string $category, int $sortOrder = 0): array
    {
        return [
            'embassy_list_id'            => $list->id,
            'agency_id'                  => $list->agency_id,
            'hr_profile_id'              => $hr->id,
            'agent_id'                   => $hr->agent_id,
            'category'                   => $category,
            'serial_no'                  => 0,
            'sort_order'                 => $sortOrder,
            'snapshot_agent_name'        => $hr->agent?->name,
            'snapshot_candidate_name'    => $hr->full_name_en,
            'snapshot_candidate_name_ar' => $hr->full_name_ar,
            'snapshot_passport_no'       => $hr->passport?->passport_number,
            'snapshot_visa_no'           => $hr->visa?->visa_number,
            'snapshot_profession_en'     => $hr->occupation,
            'snapshot_sponsor_name'      => $hr->visa?->sponsor_name,
            'snapshot_sponsor_id'        => $hr->visa?->sponsor_id,
            'snapshot_nationality'       => $hr->nationality,
        ];
    }

    private function loadAvailableHr(int $agencyId, ?int $excludeListId = null)
    {
        return HrProfile::with(['agent:id,name', 'passport:id,hr_profile_id,passport_number', 'visa:id,hr_profile_id,visa_number'])
            ->forAgency($agencyId)
            ->whereIn('status', ['active', 'inactive', 'listed'])
            ->orderBy('full_name_en')
            ->get();
    }

    private function enforcePlanLimit(): void
    {
        $user         = auth()->user();
        $subscription = $user->agency?->activeSubscription;
        $plan         = $subscription?->plan;

        if (! $plan) {
            abort(403, 'No active subscription.');
        }

        $limit = $plan->max_embassy_lists_monthly;

        if ($limit >= 999) return;

        $count = EmbassyList::forAgency($user->agency_id)
            ->thisMonth()
            ->where('status', '!=', 'cancelled')
            ->count();

        if ($count >= $limit) {
            abort(403, "Monthly embassy list limit reached ({$limit}). Please upgrade your plan.");
        }
    }

    private function generateListNo(int $agencyId): string
    {
        $year   = now()->year;
        $prefix = 'EL-' . $year . '-';

        $last = EmbassyList::where('agency_id', $agencyId)
            ->where('list_no', 'like', $prefix . '%')
            ->max('list_no');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
