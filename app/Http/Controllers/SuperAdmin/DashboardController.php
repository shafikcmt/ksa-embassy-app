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
        // Amounts are grouped by the plan's currency — different currencies are never added together.
        $byCurrency = function (string $status, $since = null): array {
            $q = Subscription::query()
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->where('subscriptions.payment_status', $status);
            if ($since) {
                $q->where('subscriptions.created_at', '>=', $since);
            }

            return $q->selectRaw('plans.currency as cur, SUM(subscriptions.amount) as total')
                ->groupBy('plans.currency')->pluck('total', 'cur')
                ->map(fn ($v) => (float) $v)->filter(fn ($v) => $v > 0)->all();
        };
        $money = [
            'total'          => $byCurrency('paid'),
            'this_month'     => $byCurrency('paid', $monthStart),
            'pending_amount' => $byCurrency('pending'),
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
