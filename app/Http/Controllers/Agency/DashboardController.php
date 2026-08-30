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
        $user = auth()->user();

        // Super admins belong on their own dashboard, not the agency one.
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        }

        // Guard against a missing agency assignment so we never pass null
        // into agencyStats(int $agencyId) and crash with a TypeError.
        $agencyId = $user->agency_id;
        if (! $agencyId) {
            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is not assigned to any agency. Please contact Super Admin.',
            ]);
        }

        $agency   = $user->agency()->with(['activeSubscription.plan', 'notices' => fn($q) => $q->active()])->first();
        $subscription = $agency?->activeSubscription;

        // Passenger status quick-search (optional). Always scoped to the user's own
        // agency — the term is only ever used as a LIKE value, no agency_id is taken
        // from the request, so an agency can only find its own passengers.
        $pq = trim((string) request('pq', ''));
        $passengerResults = null;
        if ($pq !== '') {
            $passengerResults = HrProfile::with(['agent:id,name', 'passport:id,hr_profile_id,passport_number', 'visa:id,hr_profile_id,visa_number'])
                ->where('agency_id', $agencyId)
                ->where(function ($q) use ($pq) {
                    $q->where('full_name_en', 'like', "%{$pq}%")
                      ->orWhere('full_name_ar', 'like', "%{$pq}%")
                      ->orWhere('mofa_new', 'like', "%{$pq}%")
                      ->orWhere('mofa_old', 'like', "%{$pq}%")
                      ->orWhereHas('passport', fn($p) => $p->where('passport_number', 'like', "%{$pq}%"))
                      ->orWhereHas('visa', fn($v) => $v->where('visa_number', 'like', "%{$pq}%"));
                })
                ->latest()
                ->limit(25)
                ->get();
        }

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
            'recentHr', 'upcomingExpiries', 'pq', 'passengerResults'
        ));
    }
}
