<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Subscription;
use App\Services\DashboardStatsService;

class DashboardController extends Controller
{
    public function __construct(private DashboardStatsService $statsService) {}

    public function index()
    {
        $stats = $this->statsService->superAdminStats();

        $recentAgencies = Agency::with('activeSubscription.plan')
            ->latest()
            ->limit(8)
            ->get();

        $expiringSubscriptions = Subscription::with(['agency', 'plan'])
            ->active()
            ->where('end_date', '<=', now()->addDays(14))
            ->orderBy('end_date')
            ->limit(8)
            ->get();

        $topAgencies     = $this->statsService->superAdminTopAgencies(6);
        $recentAuditLogs = $this->statsService->superAdminRecentAuditLogs(10);

        return view('super-admin.dashboard', compact(
            'stats', 'recentAgencies', 'expiringSubscriptions',
            'topAgencies', 'recentAuditLogs'
        ));
    }
}
