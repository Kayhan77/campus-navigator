<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Building;

class BuildingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Building $building): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Building $building): bool
    {
        return $user->isAdmin();
    }
}
