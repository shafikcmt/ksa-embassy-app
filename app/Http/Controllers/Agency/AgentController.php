<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Models\Agent;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Agent::class);

        $agencyId = auth()->user()->agency_id;

        $query = Agent::where('agency_id', $agencyId)
            ->with(['createdBy:id,name']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $agents = $query->latest()->paginate(15)->withQueryString();

        $totalAgents  = Agent::where('agency_id', $agencyId)->count();
        $activeAgents = Agent::where('agency_id', $agencyId)->where('status', 'active')->count();

        // Plan limit
        $subscription = auth()->user()->agency?->activeSubscription;
        $planLimit    = $subscription?->plan?->max_agents ?? null;

        return view('agency.agents.index', compact(
            'agents', 'totalAgents', 'activeAgents', 'planLimit'
        ));
    }

    public function create()
    {
        $this->authorize('create', Agent::class);
        $this->enforcePlanLimit();

        return view('agency.agents.create');
    }

    public function store(StoreAgentRequest $request)
    {
        $this->authorize('create', Agent::class);
        $this->enforcePlanLimit();

        $agent = Agent::create(array_merge($request->validated(), [
            'agency_id'  => auth()->user()->agency_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        AuditLog::record('create_agent', $agent, [], $agent->toArray());

        return redirect()->route('agents.show', $agent)
            ->with('success', 'Agent "' . $agent->name . '" created successfully.');
    }

    public function show(Agent $agent)
    {
        $this->authorize('view', $agent);

        $agent->load(['createdBy', 'updatedBy', 'hrProfiles']);

        return view('agency.agents.show', compact('agent'));
    }

    public function edit(Agent $agent)
    {
        $this->authorize('update', $agent);

        return view('agency.agents.edit', compact('agent'));
    }

    public function update(UpdateAgentRequest $request, Agent $agent)
    {
        $this->authorize('update', $agent);

        $old = $agent->toArray();
        $agent->update(array_merge($request->validated(), ['updated_by' => auth()->id()]));

        AuditLog::record('update_agent', $agent, $old, $agent->fresh()->toArray());

        return redirect()->route('agents.show', $agent)
            ->with('success', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent)
    {
        $this->authorize('delete', $agent);

        $name = $agent->name;
        AuditLog::record('delete_agent', $agent, $agent->toArray(), []);
        $agent->delete();

        return redirect()->route('agents.index')
            ->with('success', "Agent \"$name\" deleted.");
    }

    private function enforcePlanLimit(): void
    {
        $user         = auth()->user();
        $subscription = $user->agency?->activeSubscription;
        $planLimit    = $subscription?->plan?->max_agents;

        if ($planLimit === null) return;

        // 999 = unlimited (Premium)
        if ($planLimit >= 999) return;

        $currentCount = Agent::where('agency_id', $user->agency_id)->count();
        if ($currentCount >= $planLimit) {
            abort(403, "Your plan allows a maximum of {$planLimit} agents. Please upgrade to add more.");
        }
    }
}
