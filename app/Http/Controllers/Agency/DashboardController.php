<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
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

        $recentHr = HrProfile::where('agency_id', $agencyId)
            ->with(['passport', 'visa', 'agent'])
            ->latest()
            ->limit(6)
            ->get();

        // Real upcoming dates for the reminders panel
        $upcomingExpiries = Passport::whereHas('hrProfile', fn($q) => $q->where('agency_id', $agencyId))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->startOfDay(), now()->copy()->addMonths(6)])
            ->with('hrProfile:id,full_name_en')
            ->orderBy('expiry_date')
            ->limit(5)
            ->get();

        return view('agency.dashboard', compact(
            'agency', 'subscription', 'stats', 'alerts',
            'recentHr', 'upcomingExpiries'
        ));
    }
}
