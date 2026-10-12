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

        // Money (BDT) — what the platform has collected and what is still waiting for a payment.
        $monthStart = now()->startOfMonth();
        $money = [
            'total'          => (float) Subscription::where('payment_status', 'paid')->sum('amount'),
            'this_month'     => (float) Subscription::where('payment_status', 'paid')->where('created_at', '>=', $monthStart)->sum('amount'),
            'pending_amount' => (float) Subscription::where('payment_status', 'pending')->sum('amount'),
            'pending_count'  => Subscription::where('payment_status', 'pending')->count(),
        ];
        $pendingPayments = Subscription::with(['agency', 'plan'])
            ->where('payment_status', 'pending')->latest()->limit(6)->get();

        // Agency licenses that expire (or already expired) within 30 days — the renewal follow-up list.
        $licenseDue = Agency::whereNotNull('license_expiry_date')
            ->where('status', 'active')
            ->whereDate('license_expiry_date', '<=', now()->addDays(30))
            ->orderBy('license_expiry_date')->limit(6)->get();
        $licenseDueCount = Agency::whereNotNull('license_expiry_date')->where('status', 'active')
            ->whereDate('license_expiry_date', '<=', now()->addDays(30))->count();

        return view('super-admin.dashboard', compact(
            'stats', 'recentAgencies', 'expiringSubscriptions',
            'topAgencies', 'recentAuditLogs', 'money', 'pendingPayments', 'licenseDue', 'licenseDueCount'
        ));
    }
}
