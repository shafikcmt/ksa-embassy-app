<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\HrFieldControls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
            'user', 'agency', 'printHeader', 'printFooter', 'notifySubscription', 'notifyPassport',
            'canManageFields', 'hrFieldGroups', 'hrFieldStatuses'
        ));
    }

    public function update(Request $request)
    {
        $tab = $request->input('tab', 'profile');

        if ($tab === 'profile') {
            $user   = auth()->user();
            $agency = $user->agency;

            // Login email is changing only if a different value was submitted.
            $loginEmailChanging = $request->filled('login_email')
                && $request->input('login_email') !== $user->email;

            $validated = $request->validate([
                'owner_name'      => 'nullable|string|max:200',
                'official_email'  => 'nullable|email|max:150',
                'phone'           => 'nullable|string|max:30',
                'address'         => 'nullable|string|max:500',
                'print_logo'      => 'required|boolean',
                'login_email'     => [
                    'required', 'email', 'max:150',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                // Current password is required only when the login email changes.
                'current_password' => [
                    Rule::requiredIf($loginEmailChanging),
                ],
            ]);

            if ($loginEmailChanging
                && ! Hash::check((string) $request->input('current_password'), $user->password)) {
                return back()
                    ->withErrors(['current_password' => 'The current password is incorrect.'])
                    ->withInput()
                    ->withFragment('profile');
            }

            // Agency-owned, editable fields. Company name (name) and RL number are
            // license/registration data and are intentionally NOT updated here.
            $agency->update([
                'owner_name' => $validated['owner_name'] ?? null,
                'email'      => $validated['official_email'] ?? null,
                'phone'      => $validated['phone'] ?? null,
                'address'    => $validated['address'] ?? null,
                'print_logo' => (bool) $validated['print_logo'],
            ]);

            // Login / account email lives on the user record.
            if ($loginEmailChanging) {
                $user->update(['email' => $validated['login_email']]);
            }

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
