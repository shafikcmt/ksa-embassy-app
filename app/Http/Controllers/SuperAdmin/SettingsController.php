<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use App\Support\HrFieldControls;
use App\Support\SiteSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $plans = Plan::active()->orderBy('price')->get();

        $defaultCurrency = \App\Support\Money::defaultCode();
        $systemName     = Setting::get('system_name', null, 'VisaDeskPro');
        $defaultPlanId  = Setting::get('default_plan_id', null, '');
        $maintenanceMode = Setting::get('maintenance_mode', null, '0');
        $supportEmail   = Setting::get('support_email', null, '');

        // License renewal / payment details (global — the platform owner's payment
        // channels shown read-only to agencies on their License page).
        $renewalAmount       = Setting::get('renewal_amount', null, '');
        $bkashNumber         = Setting::get('bkash_number', null, '');
        $nagadNumber         = Setting::get('nagad_number', null, '');
        $bankDetails         = Setting::get('bank_details', null, '');
        $renewalInstructions = Setting::get('renewal_instructions', null, '');

        // Global default HR form field controls (agencies inherit these unless overridden).
        $hrFieldGroups   = HrFieldControls::grouped();
        $hrFieldStatuses = HrFieldControls::statusesForScope(null);

        $site = SiteSettings::all();

        return view('super-admin.settings.index', compact(
            'site', 'defaultCurrency',
            'plans', 'systemName', 'defaultPlanId', 'maintenanceMode', 'supportEmail',
            'renewalAmount', 'bkashNumber', 'nagadNumber', 'bankDetails', 'renewalInstructions',
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

        // Public website content: WhatsApp, contact, social, pixel, hero text, trust numbers.
        if ($request->input('section') === 'website') {
            $rules = ['site_show_trust' => 'nullable|boolean'];
            foreach (SiteSettings::FIELDS as $key => [$default, $max]) {
                if ($key !== 'site_show_trust') {
                    $rules[$key] = 'nullable|string|max:'.$max;
                }
            }
            $rules['site_facebook_url'] = 'nullable|url|max:300';
            $rules['site_youtube_url']  = 'nullable|url|max:300';
            $rules['site_meta_pixel_id'] = ['nullable', 'regex:/^\d{5,20}$/'];
            $request->validate($rules);

            SiteSettings::save($request->all());

            return back()->with('success', 'Website & marketing settings saved.');
        }

        // License renewal / payment details (own form/section).
        if ($request->input('section') === 'payment') {
            $request->validate([
                'renewal_amount'       => 'nullable|string|max:100',
                'bkash_number'         => 'nullable|string|max:50',
                'nagad_number'         => 'nullable|string|max:50',
                'bank_details'         => 'nullable|string|max:1000',
                'renewal_instructions' => 'nullable|string|max:2000',
            ]);

            Setting::set('renewal_amount', $request->input('renewal_amount', ''));
            Setting::set('bkash_number', $request->input('bkash_number', ''));
            Setting::set('nagad_number', $request->input('nagad_number', ''));
            Setting::set('bank_details', $request->input('bank_details', ''));
            Setting::set('renewal_instructions', $request->input('renewal_instructions', ''));

            return back()->with('success', 'License renewal / payment details saved.');
        }

        $request->validate([
            'system_name'    => 'required|string|max:200',
            'support_email'  => 'nullable|email|max:150',
            'default_plan_id'=> 'nullable|exists:plans,id',
            'default_currency' => 'required|in:'.implode(',', \App\Support\Money::codes()),
        ]);

        Setting::set('system_name', $request->input('system_name', 'VisaDeskPro'));
        Setting::set('support_email', $request->input('support_email', ''));
        Setting::set('default_plan_id', $request->input('default_plan_id', ''));
        Setting::set('default_currency', $request->input('default_currency', 'BDT'));
        Setting::set('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0');

        return back()->with('success', 'Global settings saved.');
    }
}
