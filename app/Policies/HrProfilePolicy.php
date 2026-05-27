<?php

namespace App\Policies;

use App\Models\HrProfile;
use App\Models\User;

class HrProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->agency_id !== null;
    }

    public function view(User $user, HrProfile $hrProfile): bool
    {
        return $user->isSuperAdmin() || $user->agency_id === $hrProfile->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermissionTo('create_hr');
    }

    public function update(User $user, HrProfile $hrProfile): bool
    {
        return $user->isSuperAdmin()
            || ($user->agency_id === $hrProfile->agency_id && $user->hasPermissionTo('edit_hr'));
    }

    public function delete(User $user, HrProfile $hrProfile): bool
    {
        return $user->isSuperAdmin()
            || ($user->agency_id === $hrProfile->agency_id && $user->hasPermissionTo('delete_hr'));
    }
}
