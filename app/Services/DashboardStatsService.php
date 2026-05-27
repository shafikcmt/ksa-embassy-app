<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\EmbassyList;
use App\Models\GeneratedDocument;
use App\Models\HrProfile;
use App\Models\Passport;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardStatsService
{
    // ──────────────────────────────────────────────
    // AGENCY DASHBOARD
    // ──────────────────────────────────────────────

    public function agencyStats(int $agencyId): array
    {
        $now = now();

        return [
            'total_agents'         => Agent::where('agency_id', $agencyId)->count(),
            'active_agents'        => Agent::where('agency_id', $agencyId)->where('status', 'active')->count(),
            'total_hr'             => HrProfile::where('agency_id', $agencyId)->count(),
            'active_hr'            => HrProfile::where('agency_id', $agencyId)->where('status', 'active')->count(),
            'total_embassy_lists'  => EmbassyList::where('agency_id', $agencyId)->where('status', '!=', 'cancelled')->count(),
            'embassy_lists_month'  => EmbassyList::where('agency_id', $agencyId)
                                        ->where('status', '!=', 'cancelled')
                                        ->whereMonth('created_at', $now->month)
                                        ->whereYear('created_at', $now->year)
                                        ->count(),
            'pdf_downloads_month'  => GeneratedDocument::where('agency_id', $agencyId)
                                        ->where('action', 'download')
                                        ->whereMonth('created_at', $now->month)
                                        ->whereYear('created_at', $now->year)
                                        ->count(),
            'hr_no_passport'       => HrProfile::where('agency_id', $agencyId)
                                        ->whereDoesntHave('passport', fn($q) => $q->whereNotNull('passport_number'))
                                        ->where('status', 'active')
                                        ->count(),
            'hr_draft_embassy'     => EmbassyList::where('agency_id', $agencyId)
                                        ->where('status', 'draft')
                                        ->count(),
            'passports_expiring'   => Passport::whereHas('hrProfile', fn($q) => $q->where('agency_id', $agencyId))
                                        ->whereNotNull('expiry_date')
                                        ->whereBetween('expiry_date', [$now, $now->copy()->addMonths(6)])
                                        ->count(),
        ];
    }

    public function agencyAlerts(int $agencyId, ?object $subscription, ?object $agency): array
    {
        $alerts = [];
        $now    = now();

        // Subscription alerts
        if (! $subscription) {
            $alerts[] = ['type' => 'danger', 'icon' => 'bi-credit-card-x', 'message' => 'No active subscription. You cannot create new records or generate PDFs.', 'action' => route('subscription.expired'), 'action_label' => 'Request Renewal'];
        } elseif ($subscription->daysRemaining() <= 3) {
            $alerts[] = ['type' => 'danger', 'icon' => 'bi-clock', 'message' => "Subscription expires in <strong>{$subscription->daysRemaining()} day(s)</strong> on {$subscription->end_date->format('d M Y')}. Renew now to avoid disruption.", 'action' => route('subscription.expired'), 'action_label' => 'Renew'];
        } elseif ($subscription->daysRemaining() <= 7) {
            $alerts[] = ['type' => 'warning', 'icon' => 'bi-clock', 'message' => "Subscription expires in <strong>{$subscription->daysRemaining()} days</strong> on {$subscription->end_date->format('d M Y')}.", 'action' => route('subscription.expired'), 'action_label' => 'Renew'];
        }

        // License expiry
        if ($agency?->license_expiry_date) {
            $days = (int) $now->diffInDays($agency->license_expiry_date, false);
            if ($days >= 0 && $days <= 30) {
                $alerts[] = ['type' => 'warning', 'icon' => 'bi-file-earmark-x', 'message' => "Agency license expires in <strong>{$days} day(s)</strong> on {$agency->license_expiry_date->format('d M Y')}. Please renew.", 'action' => null, 'action_label' => null];
            } elseif ($days < 0) {
                $alerts[] = ['type' => 'danger', 'icon' => 'bi-file-earmark-x', 'message' => "Agency license has <strong>expired</strong> on {$agency->license_expiry_date->format('d M Y')}. Please renew immediately.", 'action' => null, 'action_label' => null];
            }
        }

        // PDF limit
        if ($subscription) {
            $pdfLimit = $subscription->plan->max_pdf_monthly ?? 0;
            if ($pdfLimit > 0 && $pdfLimit < 9999) {
                $pdfUsed = GeneratedDocument::where('agency_id', $agencyId)
                    ->where('action', 'download')
                    ->whereMonth('created_at', $now->month)
                    ->whereYear('created_at', $now->year)
                    ->count();
                $pct = ($pdfUsed / $pdfLimit) * 100;
                if ($pdfUsed >= $pdfLimit) {
                    $alerts[] = ['type' => 'danger', 'icon' => 'bi-printer', 'message' => "Monthly PDF download limit reached (<strong>{$pdfUsed}/{$pdfLimit}</strong>). Upgrade your plan or wait until next month.", 'action' => null, 'action_label' => null];
                } elseif ($pct >= 80) {
                    $alerts[] = ['type' => 'warning', 'icon' => 'bi-printer', 'message' => "PDF download usage at <strong>{$pdfUsed}/{$pdfLimit}</strong> (" . round($pct) . "%). Approaching monthly limit.", 'action' => null, 'action_label' => null];
                }
            }
        }

        // Passports expiring within 6 months
        $expiring = Passport::whereHas('hrProfile', fn($q) => $q->where('agency_id', $agencyId))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$now, $now->copy()->addMonths(6)])
            ->count();
        if ($expiring > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'bi-passport', 'message' => "<strong>{$expiring}</strong> candidate passport(s) will expire within the next 6 months.", 'action' => route('hr.index', ['filter' => 'passport_expiring']), 'action_label' => 'View'];
        }

        // Incomplete HR records (active, no passport)
        $incomplete = HrProfile::where('agency_id', $agencyId)
            ->whereDoesntHave('passport', fn($q) => $q->whereNotNull('passport_number'))
            ->where('status', 'active')
            ->count();
        if ($incomplete > 0) {
            $alerts[] = ['type' => 'info', 'icon' => 'bi-person-exclamation', 'message' => "<strong>{$incomplete}</strong> active HR profile(s) missing passport information.", 'action' => route('hr.index'), 'action_label' => 'View'];
        }

        // Draft embassy lists
        $drafts = EmbassyList::where('agency_id', $agencyId)->where('status', 'draft')->count();
        if ($drafts > 0) {
            $alerts[] = ['type' => 'info', 'icon' => 'bi-list-ol', 'message' => "<strong>{$drafts}</strong> embassy list(s) still in draft — finalize to submit to embassy.", 'action' => route('embassy-lists.index', ['status' => 'draft']), 'action_label' => 'View'];
        }

        return $alerts;
    }

    public function agencyRecentDocActivity(int $agencyId, int $limit = 8): Collection
    {
        return GeneratedDocument::where('agency_id', $agencyId)
            ->with(['generatedBy', 'hrProfile'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    // ──────────────────────────────────────────────
    // SUPER ADMIN DASHBOARD
    // ──────────────────────────────────────────────

    public function superAdminStats(): array
    {
        $now = now();

        return [
            'total_agencies'        => Agency::count(),
            'active_agencies'       => Agency::where('status', 'active')->count(),
            'suspended_agencies'    => Agency::where('status', 'suspended')->count(),
            'active_subscriptions'  => Subscription::active()->count(),
            'expired_subscriptions' => Subscription::where('status', 'expired')->count(),
            'total_users'           => User::where('is_super_admin', false)->count(),
            'total_agents'          => Agent::count(),
            'total_hr'              => HrProfile::count(),
            'total_embassy_lists'   => EmbassyList::where('status', '!=', 'cancelled')->count(),
            'total_documents'       => GeneratedDocument::where('action', 'download')->count(),
            'docs_this_month'       => GeneratedDocument::where('action', 'download')
                                        ->whereMonth('created_at', $now->month)
                                        ->whereYear('created_at', $now->year)
                                        ->count(),
        ];
    }

    public function superAdminTopAgencies(int $limit = 5): Collection
    {
        return Agency::withCount(['hrProfiles', 'embassyLists'])
            ->with('activeSubscription.plan')
            ->orderByDesc('hr_profiles_count')
            ->limit($limit)
            ->get();
    }

    public function superAdminRecentAuditLogs(int $limit = 10): Collection
    {
        return AuditLog::with(['user', 'agency'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
