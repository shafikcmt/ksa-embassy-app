<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
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

        return view('super-admin.settings.index', compact(
            'plans', 'systemName', 'defaultPlanId', 'maintenanceMode', 'supportEmail'
        ));
    }

    public function update(Request $request)
    {
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
