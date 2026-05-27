<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $query = Agent::with(['agency:id,name', 'createdBy:id,name']);

        if ($request->filled('agency_id')) {
            $query->where('agency_id', $request->agency_id);
        }

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

        $agents   = $query->latest()->paginate(20)->withQueryString();
        $agencies = Agency::orderBy('name')->get(['id', 'name']);

        return view('super-admin.agents.index', compact('agents', 'agencies'));
    }

    public function show(Agent $agent)
    {
        $agent->load(['agency', 'createdBy', 'updatedBy']);
        return view('super-admin.agents.show', compact('agent'));
    }

    public function destroy(Agent $agent)
    {
        $name = $agent->name;
        AuditLog::record('sa_delete_agent', $agent, $agent->toArray(), []);
        $agent->delete();

        return redirect()->route('super-admin.agents.index')
            ->with('success', "Agent \"$name\" deleted.");
    }
}
