<?php

namespace App\View\Composers;

use App\Models\Notice;
use App\Services\DashboardStatsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Feeds the topbar notification bell on the Tailwind agency layout.
 *
 * Notifications are derived from two sources:
 *   1. Live agency alerts (subscription, passports, drafts…) from DashboardStatsService.
 *   2. Super-admin broadcast notices (Notice model).
 *
 * The list is role-aware: agency staff do not see billing/subscription items.
 * "Read" state is tracked client-side (Alpine + localStorage) against the
 * provided signature, so no extra table is required for this first pass.
 */
class NotificationComposer
{
    public function __construct(private DashboardStatsService $stats) {}

    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user || ! $user->agency_id) {
            $view->with([
                'notifications'        => collect(),
                'notificationCount'    => 0,
                'notificationSignature' => '',
            ]);
            return;
        }

        $agencyId = $user->agency_id;
        $isAdmin  = method_exists($user, 'isAgencyAdmin') ? $user->isAgencyAdmin() : true;

        // Cache the heavier alert/notice computation briefly (per agency).
        $items = Cache::remember("notif_items_{$agencyId}", 120, function () use ($agencyId) {
            $agency       = \App\Models\Agency::with('activeSubscription.plan')->find($agencyId);
            $subscription = $agency?->activeSubscription;

            $alerts = $this->stats->agencyAlerts($agencyId, $subscription, $agency);

            $notices = Notice::active()->forAgency($agencyId)->latest()->take(8)->get()
                ->map(fn (Notice $n) => [
                    'scope'   => 'operations',
                    'type'    => in_array($n->type, ['danger', 'warning', 'info', 'success']) ? $n->type : 'info',
                    'icon'    => 'bi-megaphone',
                    'title'   => $n->title,
                    'message' => $n->body,
                    'action'  => null,
                    'action_label' => null,
                    'time'    => optional($n->created_at)->diffForHumans(),
                ])->all();

            return array_merge($alerts, $notices);
        });

        // Role filter: staff don't see billing/subscription/license items.
        $items = collect($items)->reject(
            fn ($i) => ! $isAdmin && ($i['scope'] ?? 'operations') === 'billing'
        )->values();

        $view->with([
            'notifications'         => $items,
            'notificationCount'     => $items->count(),
            'notificationSignature' => md5($items->pluck('title')->implode('|') . $items->count()),
        ]);
    }
}
