<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\HrFieldControls;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $user   = auth()->user();
        $agency = $user->agency;

        $printHeader = Setting::get('print_header', $agency->id, '');
        $printFooter = Setting::get('print_footer', $agency->id, '');
        $notifySubscription = Setting::get('notify_subscription_expiry', $agency->id, '1');
        $notifyPassport = Setting::get('notify_passport_expiry', $agency->id, '1');

        // HR form field controls (Active/Inactive) — managed by Agency Admin / Super Admin.
        $canManageFields = $user->isAgencyAdmin() || $user->isSuperAdmin();
        $hrFieldGroups   = HrFieldControls::grouped();
        $hrFieldStatuses = HrFieldControls::statusesForScope($agency->id, true);

        return view('agency.settings.index', compact(
            'agency', 'printHeader', 'printFooter', 'notifySubscription', 'notifyPassport',
            'canManageFields', 'hrFieldGroups', 'hrFieldStatuses'
        ));
    }

    public function update(Request $request)
    {
        $tab = $request->input('tab', 'profile');

        if ($tab === 'profile') {
            $request->validate([
                'name'    => 'required|string|max:200',
                'email'   => 'nullable|email|max:150',
                'phone'   => 'nullable|string|max:30',
                'address' => 'nullable|string|max:500',
            ]);

            auth()->user()->agency->update($request->only('name', 'email', 'phone', 'address'));

            return back()->with('success', 'Profile settings saved.')->withFragment('profile');
        }

        if ($tab === 'print') {
            $request->validate([
                'print_header' => 'nullable|string|max:500',
                'print_footer' => 'nullable|string|max:500',
            ]);

            $agencyId = auth()->user()->agency_id;
            Setting::set('print_header', $request->input('print_header', ''), $agencyId);
            Setting::set('print_footer', $request->input('print_footer', ''), $agencyId);

            return back()->with('success', 'Print settings saved.')->withFragment('print');
        }

        if ($tab === 'hr_fields') {
            $user = auth()->user();
            abort_unless($user->isAgencyAdmin() || $user->isSuperAdmin(), 403);

            HrFieldControls::save($request->input('fields', []), $user->agency_id);

            return back()->with('success', 'HR form field settings saved.')->withFragment('hr-fields');
        }

        if ($tab === 'notifications') {
            $agencyId = auth()->user()->agency_id;
            Setting::set('notify_subscription_expiry', $request->boolean('notify_subscription_expiry') ? '1' : '0', $agencyId);
            Setting::set('notify_passport_expiry', $request->boolean('notify_passport_expiry') ? '1' : '0', $agencyId);

            return back()->with('success', 'Notification settings saved.')->withFragment('notifications');
        }

        return back()->with('error', 'Unknown settings tab.');
    }
}
