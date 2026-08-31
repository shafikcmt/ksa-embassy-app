<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\ErpSetting;
use Illuminate\Http\Request;

/**
 * ERP Settings — E0.
 *
 * Stores the agency's opening balance and Profit/Loss privacy controls. The
 * security code is written through the ErpSetting model mutator, which hashes
 * it (bcrypt) before persistence. Everything is scoped to the caller's own
 * agency_id (never null) so agencies can only read/write their own row.
 */
class SettingsController extends Controller
{
    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $settings = ErpSetting::forAgency($agencyId)->first() ?? new ErpSetting();

        return view('erp.settings.index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $agencyId = auth()->user()->agency_id;

        $validated = $request->validate([
            'opening_balance'      => ['required', 'numeric', 'between:-9999999999.99,9999999999.99'],
            'opening_balance_note' => ['nullable', 'string', 'max:255'],
            'double_mofa_rate'     => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'pl_security_code'     => ['nullable', 'string', 'min:4', 'max:100'],
            'clear_security_code'  => ['nullable', 'boolean'],
            'pl_visible_to_all'    => ['nullable', 'boolean'],
        ]);

        $settings = ErpSetting::firstOrNew(['agency_id' => $agencyId]);

        $settings->agency_id            = $agencyId;
        $settings->opening_balance      = $validated['opening_balance'];
        $settings->opening_balance_note = $validated['opening_balance_note'] ?? null;
        $settings->double_mofa_rate     = $validated['double_mofa_rate'];
        $settings->pl_visible_to_all    = $request->boolean('pl_visible_to_all');

        // Security code: explicit clear wins; otherwise only overwrite when a new
        // code is supplied (blank field leaves the existing hash untouched).
        if ($request->boolean('clear_security_code')) {
            $settings->pl_security_code = null;
        } elseif (! empty($validated['pl_security_code'])) {
            $settings->pl_security_code = $validated['pl_security_code']; // hashed by mutator
        }

        $settings->save();

        return redirect()
            ->route('erp.settings')
            ->with('success', 'ERP settings saved.');
    }
}
