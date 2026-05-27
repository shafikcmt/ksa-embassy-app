<?php

namespace App\Policies;

use App\Models\EmbassyList;
use App\Models\User;

class EmbassyListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->agency_id !== null;
    }

    public function view(User $user, EmbassyList $embassyList): bool
    {
        return $user->isSuperAdmin() || $user->agency_id === $embassyList->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermissionTo('create_embassy_list');
    }

    public function update(User $user, EmbassyList $embassyList): bool
    {
        return $user->isSuperAdmin()
            || ($user->agency_id === $embassyList->agency_id && $user->hasPermissionTo('edit_embassy_list'));
    }

    public function delete(User $user, EmbassyList $embassyList): bool
    {
        return $user->isSuperAdmin()
            || ($user->agency_id === $embassyList->agency_id && $user->hasPermissionTo('delete_embassy_list'));
    }

    public function finalize(User $user, EmbassyList $embassyList): bool
    {
        return $user->isSuperAdmin()
            || ($user->agency_id === $embassyList->agency_id && $user->hasPermissionTo('edit_embassy_list'));
    }

    public function cancel(User $user, EmbassyList $embassyList): bool
    {
        return $user->isSuperAdmin()
            || ($user->agency_id === $embassyList->agency_id
                && ($user->hasPermissionTo('edit_embassy_list') || $user->hasPermissionTo('delete_embassy_list')));
    }
}
