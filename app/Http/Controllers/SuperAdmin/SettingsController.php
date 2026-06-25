<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Support\HrFieldControls;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $plans = Plan::active()->orderBy('price')->get();

        $systemName     = Setting::get('system_name', null, 'VisaDeskPro');
        $defaultPlanId  = Setting::get('default_plan_id', null, '');
        $maintenanceMode = Setting::get('maintenance_mode', null, '0');
        $supportEmail   = Setting::get('support_email', null, '');

        // Global default HR form field controls (agencies inherit these unless overridden).
        $hrFieldGroups   = HrFieldControls::grouped();
        $hrFieldStatuses = HrFieldControls::statusesForScope(null);

        return view('super-admin.settings.index', compact(
            'plans', 'systemName', 'defaultPlanId', 'maintenanceMode', 'supportEmail',
            'hrFieldGroups', 'hrFieldStatuses'
        ));
    }

    public function update(Request $request)
    {
        // Global default HR form field controls (separate form on the settings page).
        if ($request->input('section') === 'hr_fields') {
            HrFieldControls::save($request->input('fields', []), null);

            return back()->with('success', 'Default HR form field settings saved.');
        }

        $request->validate([
            'system_name'    => 'required|string|max:200',
            'support_email'  => 'nullable|email|max:150',
            'default_plan_id'=> 'nullable|exists:plans,id',
        ]);

        Setting::set('system_name', $request->input('system_name', 'VisaDeskPro'));
        Setting::set('support_email', $request->input('support_email', ''));
        Setting::set('default_plan_id', $request->input('default_plan_id', ''));
        Setting::set('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0');

        return back()->with('success', 'Global settings saved.');
    }
}
