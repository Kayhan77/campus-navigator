<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class BuildingPolicy
{
    use AuthorizesByRole;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function update(User $user, Building $building): bool
    {
        return $this->canManageContent($user);
    }

    public function delete(User $user, Building $building): bool
    {
        return $this->canManageContent($user);
    }
}
