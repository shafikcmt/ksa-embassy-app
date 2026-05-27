<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with(['agency', 'plan'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('agency_id')) {
            $query->where('agency_id', $request->agency_id);
        }

        $subscriptions = $query->paginate(15)->withQueryString();
        $agencies = Agency::orderBy('name')->get(['id', 'name']);
        return view('super-admin.subscriptions.index', compact('subscriptions', 'agencies'));
    }

    public function create()
    {
        $agencies = Agency::orderBy('name')->get(['id', 'name']);
        $plans = Plan::active()->get();
        return view('super-admin.subscriptions.create', compact('agencies', 'plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'agency_id'      => 'required|exists:agencies,id',
            'plan_id'        => 'required|exists:plans,id',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'status'         => 'required|in:trial,active,expired,suspended',
            'payment_status' => 'required|in:pending,paid,failed,waived',
            'amount'         => 'required|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        $subscription = Subscription::create($validated);
        AuditLog::record('create_subscription', $subscription);

        return redirect()->route('super-admin.subscriptions.index')
            ->with('success', 'Subscription assigned successfully.');
    }

    public function edit(Subscription $subscription)
    {
        $agencies = Agency::orderBy('name')->get(['id', 'name']);
        $plans = Plan::active()->get();
        return view('super-admin.subscriptions.edit', compact('subscription', 'agencies', 'plans'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'plan_id'        => 'required|exists:plans,id',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'status'         => 'required|in:trial,active,expired,suspended',
            'payment_status' => 'required|in:pending,paid,failed,waived',
            'amount'         => 'required|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        $subscription->update($validated);
        AuditLog::record('update_subscription', $subscription);

        return redirect()->route('super-admin.subscriptions.index')
            ->with('success', 'Subscription updated.');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return redirect()->route('super-admin.subscriptions.index')->with('success', 'Subscription deleted.');
    }

    public function approvePayment(Subscription $subscription)
    {
        $subscription->update([
            'payment_status' => 'paid',
            'status'         => 'active',
        ]);
        AuditLog::record('approve_payment', $subscription);
        return back()->with('success', 'Payment approved and subscription activated.');
    }
}
