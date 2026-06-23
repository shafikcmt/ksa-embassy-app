<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\EmbassyList;
use App\Models\HrProfile;
use App\Models\Passport;
use App\Services\DashboardStatsService;

class DashboardController extends Controller
{
    public function __construct(private DashboardStatsService $statsService) {}

    public function index()
    {
        $user     = auth()->user();
        $agency   = $user->agency()->with(['activeSubscription.plan', 'notices' => fn($q) => $q->active()])->first();
        $subscription = $agency?->activeSubscription;
        $agencyId = $user->agency_id;

        $stats   = $this->statsService->agencyStats($agencyId);
        $alerts  = $this->statsService->agencyAlerts($agencyId, $subscription, $agency);
        $recentDocActivity = $this->statsService->agencyRecentDocActivity($agencyId);

        $recentHr = HrProfile::where('agency_id', $agencyId)
            ->with(['passport', 'visa', 'agent'])
            ->latest()
            ->limit(6)
            ->get();

        $recentEmbassyLists = EmbassyList::where('agency_id', $agencyId)
            ->latest('list_date')
            ->limit(5)
            ->get();

        // Embassy-list status breakdown (powers the Application Status Overview)
        $embassyStatusCounts = EmbassyList::where('agency_id', $agencyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Real upcoming dates for the reminders / calendar panel
        $upcomingExpiries = Passport::whereHas('hrProfile', fn($q) => $q->where('agency_id', $agencyId))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->startOfDay(), now()->copy()->addMonths(6)])
            ->with('hrProfile:id,full_name_en')
            ->orderBy('expiry_date')
            ->limit(5)
            ->get();

        return view('agency.dashboard', compact(
            'agency', 'subscription', 'stats', 'alerts',
            'recentHr', 'recentEmbassyLists', 'recentDocActivity',
            'embassyStatusCounts', 'upcomingExpiries'
        ));
    }
}
