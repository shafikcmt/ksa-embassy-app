<?php

namespace App\Policies;

use App\Models\SmartNote;
use App\Models\User;

class SmartNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function view(User $user, SmartNote $note): bool
    {
        return $user->agency_id !== null && $user->agency_id === $note->agency_id;
    }

    public function create(User $user): bool
    {
        return $user->agency_id !== null;
    }

    public function update(User $user, SmartNote $note): bool
    {
        return $user->agency_id !== null && $user->agency_id === $note->agency_id;
    }

    public function delete(User $user, SmartNote $note): bool
    {
        return $user->agency_id !== null && $user->agency_id === $note->agency_id;
    }

    public function restore(User $user, SmartNote $note): bool
    {
        return $user->agency_id !== null && $user->agency_id === $note->agency_id;
    }

    public function forceDelete(User $user, SmartNote $note): bool
    {
        return $user->agency_id !== null && $user->agency_id === $note->agency_id;
    }
}
