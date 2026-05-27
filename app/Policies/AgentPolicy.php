<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->agency_id !== null;
    }

    public function view(User $user, Agent $agent): bool
    {
        return $user->isSuperAdmin() || $user->agency_id === $agent->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermissionTo('create_agents');
    }

    public function update(User $user, Agent $agent): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->agency_id === $agent->agency_id && $user->hasPermissionTo('edit_agents');
    }

    public function delete(User $user, Agent $agent): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->agency_id === $agent->agency_id && $user->hasPermissionTo('delete_agents');
    }

    public function restore(User $user, Agent $agent): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Agent $agent): bool
    {
        return $user->isSuperAdmin();
    }
}
