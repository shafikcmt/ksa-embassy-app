<?php

namespace App\Http\Middleware;

use App\Models\ErpSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the owner-only Profit/Loss screen (E5).
 *
 * Applied as `pl-unlocked` on the P/L data route and both export routes. It
 * lets the request through only when P/L is genuinely accessible:
 *   - no security code is configured (nothing to unlock), OR
 *   - pl_visible_to_all is on (agency opted out of the gate), OR
 *   - a session unlock exists and has NOT passed its absolute TTL.
 *
 * Otherwise a GET is redirected to the unlock screen and any other verb (the
 * exports are GET too, but this is defensive) gets a 403 — so a direct hit on
 * an export URL can never generate P/L data without a live unlock.
 *
 * The unlock is a SESSION flag with an ABSOLUTE expiry (not sliding): once set,
 * it dies exactly TTL_SECONDS later regardless of activity. Admin/owner gating
 * (isAgencyAdmin) is enforced on the controller actions themselves.
 */
class EnsurePlUnlocked
{
    /** Absolute unlock lifetime — 15 minutes. */
    public const TTL_SECONDS = 900;

    /** Session key holding the unix timestamp at which the unlock expires. */
    public const SESSION_KEY = 'erp_pl_unlocked_until';

    public function handle(Request $request, Closure $next): Response
    {
        $agencyId = auth()->user()->agency_id;
        $settings = ErpSetting::forAgency($agencyId)->first();

        if (self::isAccessible($settings, $request)) {
            return $next($request);
        }

        // Locked: never render data. Redirect browsers to the unlock screen;
        // refuse anything else outright.
        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            return redirect()->route('erp.profit-loss.unlock')
                ->with('error', 'Enter the Profit/Loss security code to continue.');
        }

        abort(403);
    }

    /**
     * Is P/L currently viewable for this request? Shared by the middleware and
     * the controller (so the unlock screen and the data view agree).
     */
    public static function isAccessible(?ErpSetting $settings, Request $request): bool
    {
        // No settings row or no code set → nothing to protect.
        if (! $settings || ! $settings->hasSecurityCode()) {
            return true;
        }

        // Agency chose to expose P/L to all (still admin-gated on the route).
        if ($settings->pl_visible_to_all) {
            return true;
        }

        $until = (int) $request->session()->get(self::SESSION_KEY, 0);

        return $until > now()->timestamp;
    }
}
