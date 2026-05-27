<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function expired()
    {
        $agency = auth()->user()->agency;
        $lastSubscription = $agency?->subscriptions()->with('plan')->latest()->first();
        return view('agency.subscription-expired', compact('agency', 'lastSubscription'));
    }

    public function renewRequest(Request $request)
    {
        $request->validate(['message' => 'nullable|string|max:500']);

        \App\Models\AuditLog::record('renewal_requested', auth()->user()->agency, [], [
            'message' => $request->message ?? 'Agency requested subscription renewal.',
        ]);

        return back()->with('success', 'Renewal request sent. Our team will contact you shortly.');
    }
}
