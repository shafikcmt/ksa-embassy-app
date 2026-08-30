<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\ErpSetting;

/**
 * ERP Suite dashboard — E0 shell.
 *
 * Renders the ERP layout + sidebar with placeholder KPI cards. No accounting
 * math runs yet; the operational trackers and ledgers arrive in later ERP
 * phases (E1+). Route-level access is enforced by page-access:erp.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $settings = ErpSetting::forAgency($agencyId)->first();

        return view('erp.dashboard', [
            'settings' => $settings,
        ]);
    }
}
