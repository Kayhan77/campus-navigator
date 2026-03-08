<?php

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;

trait AuthorizesByRole
{
    protected function canManageContent(User $user): bool
    {
        return $user->hasAnyRole(UserRole::adminRoles());
    }

    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::SuperAdmin);
    }
}
