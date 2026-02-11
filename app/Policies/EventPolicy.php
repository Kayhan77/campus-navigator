<?php

namespace App\Policies;

use App\Models\User;

class EventPolicy
{
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
