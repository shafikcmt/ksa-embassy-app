<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;

class LicenseController extends Controller
{
    /**
     * Show the current agency's license information (read-only).
     *
     * Tenancy: the license is always resolved from the authenticated user's own
     * agency (auth()->user()->agency). No agency_id is ever accepted from the
     * request, so an agency user can only ever view their own license.
     */
    public function index()
    {
        $user = auth()->user();

        // Super admins have their own agency-wide license views; keep portals separate.
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.agencies.index');
        }

        // Guard against a missing agency assignment so we never operate on a null
        // agency (mirrors DashboardController's safety check).
        $agency = $user->agency;
        if (! $agency) {
            auth()->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is not assigned to any agency. Please contact Super Admin.',
            ]);
        }

        // Days remaining until license expiry (null when no expiry date is set).
        $expiry        = $agency->license_expiry_date;
        $daysRemaining = $expiry ? (int) now()->startOfDay()->diffInDays($expiry, false) : null;

        // Status label derived purely from existing data (no schema change).
        if ($expiry === null) {
            $statusLabel = 'Not set';
            $statusTone  = 'slate';
        } elseif ($daysRemaining < 0) {
            $statusLabel = 'Expired';
            $statusTone  = 'red';
        } elseif ($daysRemaining <= 30) {
            $statusLabel = 'Expiring soon';
            $statusTone  = 'amber';
        } else {
            $statusLabel = 'Active';
            $statusTone  = 'green';
        }

        return view('agency.license.index', compact(
            'agency', 'daysRemaining', 'statusLabel', 'statusTone'
        ));
    }
}
