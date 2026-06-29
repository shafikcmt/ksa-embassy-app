<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::withCount('subscriptions')->get();
        return view('super-admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('super-admin.plans.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);
        $validated['slug'] = Str::slug($validated['name']);
        Plan::create($validated);
        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit(Plan $plan)
    {
        return view('super-admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $this->validatePlan($request);
        $plan->update($validated);
        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete plan with active subscriptions.']);
        }
        $plan->delete();
        return redirect()->route('super-admin.plans.index')->with('success', 'Plan deleted.');
    }

    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'name'                       => 'required|string|max:100',
            'price'                      => 'required|numeric|min:0',
            'currency'                   => 'required|string|in:BDT,USD,SAR',
            'max_hr'                     => 'required|integer|min:1',
            'max_users'                  => 'required|integer|min:1',
            'max_agents'                 => 'required|integer|min:0',
            'max_embassy_lists_monthly'  => 'required|integer|min:0',
            'max_pdf_monthly'            => 'required|integer|min:0',
            'storage_limit_mb'           => 'required|integer|min:1',
            'duration_days'              => 'required|integer|min:1',
            'is_active'                  => 'boolean',
            'description'                => 'nullable|string',
        ]);
    }
}
